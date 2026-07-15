<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Client;

use CurlHandle;
use CurlMultiHandle;
use Ennacx\SimpleCurl\Entity\PreparedRequest;
use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Entity\Response;
use Ennacx\SimpleCurl\Entity\Responses;
use Ennacx\SimpleCurl\Enum\MultiCurlError;
use Ennacx\SimpleCurl\Exception\CurlExecutionException;
use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;
use Ennacx\SimpleCurl\Factory\CurlOptionsFactory;
use Ennacx\SimpleCurl\Factory\ResponseFactory;

/**
 * 複数のリクエストをcURL Multiで並列実行するクライアント。
 * 各リクエストのcURLハンドラーをMultiハンドラーへ登録し、完了したものから `Response` へ変換する。
 */
final readonly class MultiClient {

    /**
     * コンストラクタ
     *
     * @param CurlOptionsFactory $optionsFactory  PreparedRequestからcURLオプションを生成するFactory
     * @param ResponseFactory    $responseFactory cURL実行結果からResponseを生成するFactory
     */
    public function __construct(
        private CurlOptionsFactory $optionsFactory  = new CurlOptionsFactory(),
        private ResponseFactory    $responseFactory = new ResponseFactory(),
    ){
    }

    /**
     * 指定されたRequestまたはPreparedRequest群を並列実行し、Request-IDをキーにしたResponse配列を返す。
     * Requestが渡された場合は、デフォルトのCurlOptionsを使って内部でPreparedRequestへ変換する。
     * ※返却配列のキーには各Requestの `Request::$id` を使用する。
     *
     * @param  Request|PreparedRequest ...$preparedRequests 実行対象のRequestまたはPreparedRequest
     * @return Responses
     * @throws InvalidConfigurationException
     * @throws CurlExecutionException
     */
    public function sendAll(Request|PreparedRequest ...$preparedRequests): Responses {

        // Requestの変換
        $preparedRequests = array_map(function(Request|PreparedRequest $preparedRequest): PreparedRequest {
            return ($preparedRequest instanceof Request) ? $preparedRequest->prepare(options: null) : $preparedRequest;
        }, $preparedRequests);

        $cmh = curl_multi_init();

        // レスポンス返却用
        $responses = [];
        $handles   = [];

        try{
            // 並列処理するcURLそれぞれにハンドラーを設定
            foreach($preparedRequests as $preparedRequest){
                $request = $preparedRequest->getRequest();

                // ループ中の Request-ID 取得
                $requestId = $request->getId();

                $ch = curl_init();

                if($ch === false){
                    throw new CurlExecutionException(sprintf('Invalid cURL handle. Request-ID: %s', $requestId));
                }

                if(!curl_setopt_array($ch, $this->optionsFactory->fromPreparedRequest($preparedRequest))){
                    throw new InvalidConfigurationException(sprintf('Invalid cURL option or value included. Request-ID: %s', $requestId));
                }

                // cURLハンドラーを追加
                $result = curl_multi_add_handle($cmh, $ch);

                if($result !== MultiCurlError::OK->value){
                    throw new CurlExecutionException(sprintf('Failed to add cURL handle. Request-ID: %s', $requestId));
                }

                // レスポンス整理用にマッピング配列を生成
                $key = $this->generateKey($ch);
                $handles[$key] = [
                    'handle'          => $ch,
                    'preparedRequest' => $preparedRequest,
                ];
            }

            // cURLの並列実行
            $result = $this->exec($cmh, $running);

            if($result !== MultiCurlError::OK->value){
                throw new CurlExecutionException('The request could not be started. One of the settings in the multi-request may be invalid.');
            }

            // 完了しているハンドラーがあれば回収してレスポンスを生成
            $this->drainCompleted($cmh, $handles, $responses);

            // 実行中のハンドラーがいる場合
            while($running > 0){
                // 5秒、もしくはソケットの再開まで待機
                $selected = curl_multi_select($cmh, 5.0);

                if($selected === -1){
                    usleep(10000);
                }

                // cURLの並列実行で実行中のハンドラー数を更新
                $result = $this->exec($cmh, $running);

                if($result !== MultiCurlError::OK->value){
                    throw new CurlExecutionException('The request failed during multi execution.');
                }

                // 完了しているハンドラーがあれば回収してレスポンスを生成
                $this->drainCompleted($cmh, $handles, $responses);
            }
        } finally{
            // PHP 8.0以降、CurlHandle/CurlMultiHandle はオブジェクトとして管理されるため、curl_close()/curl_multi_close() は呼ばず、スコープアウト時のGCに任せる

            // マルチハンドラーにハンドラーが残り続けている場合は除去
            foreach($handles as $entry){
                if(isset($entry['handle']) && $entry['handle'] instanceof CurlHandle){
                    curl_multi_remove_handle($cmh, $entry['handle']);
                }
            }
        }

        return new Responses($responses);
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
     * @param  CurlMultiHandle                                                         $cmh
     * @param  array<int, array{handle: CurlHandle, preparedRequest: PreparedRequest}> $handles
     * @param  array<string, Response>                                                 $responses
     * @return void
     */
    private function drainCompleted(CurlMultiHandle $cmh, array &$handles, array &$responses): void {

        while($raised = curl_multi_info_read($cmh)){
            $ch = $raised['handle'];
            $key = $this->generateKey($ch);

            // 既にdrain済みの場合はマルチハンドラーから除去して次のハンドラーへ
            if(!array_key_exists($key, $handles)){
                curl_multi_remove_handle($cmh, $ch);

                continue;
            }

            // マッピング配列からループ中のハンドラーに対応するリクエストを取得
            $preparedRequest = $handles[$key]['preparedRequest'];

            $result = $raised['result'];
            $raw = ($result === CURLE_OK) ? curl_multi_getcontent($ch) : false;

            // ループ中の Request-ID 取得
            $requestId = $preparedRequest->getRequest()->getId();

            // cURL実行結果からResponseを生成
            $responses[$requestId] = $this->responseFactory->fromCurlResult($ch, $raw, $preparedRequest, $result);

            // マルチハンドラーからループ中のハンドラーを除去
            curl_multi_remove_handle($cmh, $ch);

            // CurlHandleの解放はスコープアウトに任せる

            unset($handles[$key]);
        }
    }
}
