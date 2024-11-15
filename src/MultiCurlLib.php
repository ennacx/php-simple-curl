<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

use CurlMultiHandle;
use InvalidArgumentException;
use RuntimeException;

final class MultiCurlLib {

    /** @var CurlMultiHandle マルチcURLハンドラー */
    private CurlMultiHandle $cmh;

    /** @var array<string, SimpleCurlLib> 並行実行するcURL群 */
    private array $channels = [];

    private float $timeoutSec = 3.0;

    /**
     * 並列cURLをシンプルに使用出来るようにラップしたライブラリ
     *
     * @param  SimpleCurlLib[] $channels 並行処理対象
     * @throws InvalidArgumentException
     */
    public function __construct(array $channels = []){

        $this->cmh = curl_multi_init();

        $temp = array_filter($channels, fn($channel): bool => ($channel instanceof SimpleCurlLib));
        foreach($temp as $idx => $channel){
            $id = $channel->getId();
            if(!array_key_exists($id, $this->channels)){
                $this->channels[$id] = $channel;
            } else{
                throw new InvalidArgumentException(sprintf('Channel-ID \'%s\' is duplicated.', $id));
            }
        }
    }

    /**
     * デストラクタ
     */
    public function __destruct(){

        $this->close();
        unset($this->cmh);
    }

    /**
     * マルチcURLセッションを閉じる
     *
     * @return void
     */
    public function close(): void {

        if(isset($this->cmh)){
            curl_multi_close($this->cmh);
        }
    }

    /**
     * チャネル追加
     *
     * @param  SimpleCurlLib $channel   追加チャネル
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addChannel(SimpleCurlLib $channel): self {

        $id = $channel->getId();
        if(array_key_exists($id, $this->channels)){
            throw new InvalidArgumentException(sprintf('Channel-ID \'%s\' is duplicated.', $id));
        }

        // ReturnTransferを強制有効
        $channel->setReturnTransfer(true);

        $this->channels[$id] = $channel;

        return $this;
    }

    /**
     * 登録済みのチャネルを取得
     *
     * @param  string $label
     * @return SimpleCurlLib|null
     */
    public function getChannel(string $label): ?SimpleCurlLib {
        return (array_key_exists($label, $this->channels)) ? $this->channels[$label] : null;
    }

    /**
     * 登録済みのチャネルを消去
     *
     * @param  string $label
     * @return void
     */
    public function removeChannel(string $label): void {

        if(array_key_exists($label, $this->channels)){
            unset($this->channels[$label]);
        }
    }

    /**
     * マルチcURL実行
     *
     * @return MultiResponseEntity[]
     */
    public function exec(): array {

        if(count($this->channels) === 0){
            throw new InvalidArgumentException('No channels were found');
        }

        /**
         * マルチcURL実行のサブファンクション
         *
         * @param  int|null $running
         * @return int
         */
        $executor = function(?int &$running): int {

            if(!isset($running))
                $running = MultiCurlError::CALL_MULTI_PERFORM->value;

            do{
                $result = curl_multi_exec($this->cmh, $running);
            } while($result === MultiCurlError::CALL_MULTI_PERFORM->value);

            return $result;
        };

        $curlInfoRaw = [];
        foreach($this->channels as $label => $channel){
            $curlInfoRaw[] = [
                'id'      => $channel->getId(),
                'handler' => $channel->getHandler(),
                'url'     => $channel->getUrl()
            ];

            // マルチハンドラーにcURLハンドラーを実装
            curl_multi_add_handle($this->cmh, $channel->getHandler());
        }
        $curlInfoHandler = array_column($curlInfoRaw, 'handler', 'id');
        $curlInfoUrl     = array_column($curlInfoRaw, 'url', 'id');

        $result = $executor($running);

        if(!$running || $result !== MultiCurlError::OK->value){
            throw new RuntimeException('The request could not be started. One of the settings in the multi-request may be invalid.');
        }

        $ret = [];

        // select前に全ての処理が終わっていたりすると複数の結果が入っていることがあるのでループが必要
        do switch(curl_multi_select($this->cmh, $this->timeoutSec)){
            // selectに失敗
            case -1:
                // ちょっと待ってから
                usleep(10);

                //
                $executor($running);

                // リトライ
                continue 2;

            // タイムアウト
            case 0:
                // リトライ
                continue 2;

            // どれかが成功 or 失敗
            default:
                // ステータスを更新
                $result = $executor($running);

                do if($raised = curl_multi_info_read($this->cmh, $remains)){
                    // 結果が返ってきたハンドラー
                    $ch = $raised['handle'];

                    $responseEntity = new MultiResponseEntity();

                    $responseEntity->id  = array_search($ch, $curlInfoHandler) ?: 'not found';
                    $responseEntity->url = $curlInfoUrl[$responseEntity->id] ?? '';

                    // 変化のあったcurlハンドラーを取得する
                    $info = curl_getinfo($ch);

                    $curlResult = curl_multi_getcontent($ch);

                    // ReturnTransfer無効時、またはcURL失敗時
                    if($curlResult === null){
                        $responseEntity->result = false;

                        $responseEntity->responseHeader = null;
                        $responseEntity->responseBody   = null;

                        $responseEntity->errorEnum    = CurlError::fromValue(curl_errno($ch));
                        $responseEntity->errorMessage = curl_error($ch);
                    }
                    // ReturnTransfer有効、且つcURL成功時
                    else{
                        $responseEntity->result = true;

                        $responseEntity->errorEnum    = CurlError::OK;
                        $responseEntity->errorMessage = '';

                        // ヘッダー情報とボディー情報を分割
                        if(isset($info['header_size'])){
                            $responseEntity->responseHeader = trim(substr($curlResult, 0, $info['header_size']));
                            $responseEntity->responseBody   = substr($curlResult, $info['header_size']);
                        } else{
                            $responseEntity->responseHeader = null;
                            $responseEntity->responseBody   = $curlResult;
                        }
                    }

                    $ret[] = $responseEntity;

                    curl_multi_remove_handle($this->cmh, $ch);
                } while($remains);
        } while($running);

        return $ret;
    }
}