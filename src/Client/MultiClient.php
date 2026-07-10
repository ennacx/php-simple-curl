<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Client;

use CurlHandle;
use CurlMultiHandle;
use Ennacx\SimpleCurl\Entity\ConfiguredRequest;
use Ennacx\SimpleCurl\Entity\Response;
use Ennacx\SimpleCurl\Enum\MultiCurlError;
use Ennacx\SimpleCurl\Factory\CurlOptionsFactory;
use Ennacx\SimpleCurl\Factory\ResponseFactory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 複数の `ConfiguredRequest` をcURL Multiで並列実行するクライアント。
 * 各リクエストのcURLハンドラーをMultiハンドラーへ登録し、完了したものから `Response` へ変換する。
 */
final readonly class MultiClient {

    /**
     * コンストラクタ
     *
     * @param CurlOptionsFactory $optionsFactory  ConfiguredRequestからcURLオプションを生成するFactory
     * @param ResponseFactory    $responseFactory cURL実行結果からResponseを生成するFactory
     */
    public function __construct(
        private CurlOptionsFactory $optionsFactory  = new CurlOptionsFactory(),
        private ResponseFactory    $responseFactory = new ResponseFactory(),
    ){
    }

    /**
     * 指定されたConfiguredRequest群を並列実行し、Request-IDをキーにしたResponse配列を返す。
     * ※返却配列のキーには各ConfiguredRequestが保持する `Request::$id` を使用する。
     *
     * @param  ConfiguredRequest ...$configuredRequests 実行対象のConfiguredRequest
     * @return array<string, Response>
     * @throws Throwable
     */
    public function sendAll(ConfiguredRequest ...$configuredRequests): array {

        $cmh = curl_multi_init();

        // レスポンス返却用
        $responses = [];
        $handles = [];

        try{
            foreach($configuredRequests as $configuredRequest){
                $ch = curl_init();

                if($ch === false){
                    throw new InvalidArgumentException(sprintf('Invalid cURL handle. Request-ID: %s', $configuredRequest->request->id));
                }

                $requestId = $configuredRequest->request->id;

                if(!curl_setopt_array($ch, $this->optionsFactory->fromConfiguredRequest($configuredRequest))){
                    throw new InvalidArgumentException(sprintf('Invalid cURL option or value included. Request-ID: %s', $requestId));
                }

                $result = curl_multi_add_handle($cmh, $ch);
                if($result !== MultiCurlError::OK->value){
                    throw new RuntimeException(sprintf('Failed to add cURL handle. Request-ID: %s', $requestId));
                }

                $key = $this->generateKey($ch);
                $handles[$key] = [
                    'handle'         => $ch,
                    'configuredRequest' => $configuredRequest,
                ];
            }

            $result = $this->exec($cmh, $running);
            if($result !== MultiCurlError::OK->value){
                throw new RuntimeException('The request could not be started. One of the settings in the multi-request may be invalid.');
            }

            $this->drainCompleted($cmh, $handles, $responses);

            while($running > 0){
                $selected = curl_multi_select($cmh, 5.0);
                if($selected === -1){
                    usleep(10000);
                }

                $result = $this->exec($cmh, $running);
                if($result !== MultiCurlError::OK->value){
                    throw new RuntimeException('The request failed during multi execution.');
                }

                $this->drainCompleted($cmh, $handles, $responses);
            }
        } catch(Throwable $e){
            throw $e;
        } finally{
            // PHP 8.0以降、CurlHandle/CurlMultiHandle はオブジェクトとして管理されるため、curl_close()/curl_multi_close() は呼ばず、スコープアウト時のGCに任せる

            foreach($handles as $entry){
                if(isset($entry['handle']) && $entry['handle'] instanceof CurlHandle){
                    curl_multi_remove_handle($cmh, $entry['handle']);
                }
            }
        }

        return $responses;
    }

    /**
     * CurlHandleを配列キーとして扱うための一意な整数値を生成する。
     *
     * @param  CurlHandle $ch
     * @return int
     */
    private function generateKey(CurlHandle $ch): int {
        return spl_object_id($ch);
    }

    /**
     * cURL multiを実行し、処理中ハンドラー数を更新する。
     *
     * @param  CurlMultiHandle $cmh
     * @param  int|null        $running 処理中のハンドラー数
     * @return int                      `CURLM_*` の実行結果
     */
    private function exec(CurlMultiHandle $cmh, ?int &$running): int {

        if(!isset($running)){
            $running = MultiCurlError::CALL_MULTI_PERFORM->value;
        }

        do{
            $result = curl_multi_exec($cmh, $running);
        } while($result === MultiCurlError::CALL_MULTI_PERFORM->value);

        return $result;
    }

    /**
     * 完了したハンドラーを回収し、Responseを生成して返却配列へ格納する。
     * 回収したハンドラーはMultiハンドラーから取り外す。
     *
     * @param  CurlMultiHandle                                                             $cmh
     * @param  array<int, array{handle: CurlHandle, configuredRequest: ConfiguredRequest}> $handles
     * @param  array<string, Response>                                                     $responses
     * @return void
     */
    private function drainCompleted(CurlMultiHandle $cmh, array &$handles, array &$responses): void {

        while($raised = curl_multi_info_read($cmh)){
            $ch = $raised['handle'];
            $key = $this->generateKey($ch);

            // 既にdrain済みの場合は取り外して次のハンドラーへ
            if(!array_key_exists($key, $handles)){
                curl_multi_remove_handle($cmh, $ch);

                continue;
            }

            $configuredRequest = $handles[$key]['configuredRequest'];
            $result = $raised['result'];
            $raw = ($result === CURLE_OK) ? curl_multi_getcontent($ch) : false;

            $responses[$configuredRequest->request->id] = $this->responseFactory->fromCurlResult($ch, $raw, $configuredRequest, $result);

            curl_multi_remove_handle($cmh, $ch);

            // CurlHandleの解放はスコープアウトに任せる

            unset($handles[$key]);
        }
    }
}
