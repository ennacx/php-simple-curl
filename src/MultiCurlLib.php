<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

use CurlMultiHandle;
use Ennacx\SimpleCurl\Entity\ResponseEntity;
use Ennacx\SimpleCurl\Enum\CurlError;
use Ennacx\SimpleCurl\Enum\MultiCurlError;
use Ennacx\SimpleCurl\Trait\CurlLibTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * 並列cURLをシンプルに使用出来るようにラップしたライブラリ
 */
final class MultiCurlLib {

    /* Lib共通使用トレイト */
    use CurlLibTrait;

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
    public function __construct(SimpleCurlLib ...$channels){

        $this->cmh = curl_multi_init();

        if(!empty($channels)){
            foreach($channels as $idx => $channel){
                $id = $channel->getId();

                try{
                    $this->addChannel($channel);
                } catch(InvalidArgumentException $e){
                    throw new InvalidArgumentException(sprintf('%s [Idx: %d / ID: %s]', $e->getMessage(), $idx, $id));
                }
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

        if(isset($this->cmh))
            curl_multi_close($this->cmh);
    }

    /**
     * チャネル追加
     *
     * @param  SimpleCurlLib $channel   追加チャネル
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addChannel(SimpleCurlLib $channel): self {

        $channelId = $channel->getId();
        if(empty($channelId))
            throw new InvalidArgumentException('Channel-ID is empty.');
        else if(array_key_exists($channelId, $this->channels))
            throw new InvalidArgumentException(sprintf('Channel-ID \'%s\' is duplicated.', $channelId));

        // ReturnTransferを強制有効
        $channel->setReturnTransfer(returnTransfer: true, returnHeader: true);

        $this->channels[$channelId] = $channel;

        return $this;
    }

    /**
     * 登録済みのチャネルを取得
     *
     * @param  string $channelId
     * @return SimpleCurlLib|null
     */
    public function getChannel(string $channelId): ?SimpleCurlLib {
        return (array_key_exists($channelId, $this->channels)) ? $this->channels[$channelId] : null;
    }

    /**
     * 登録済みのチャネルを消去
     *
     * @param  string $channelId
     * @return void
     */
    public function removeChannel(string $channelId): void {

        if(array_key_exists($channelId, $this->channels))
            unset($this->channels[$channelId]);
    }

    /**
     * 登録済みチャネルのID群を取得
     *
     * @return string[]
     */
    public function getChannelIds(): array {
        return array_keys($this->channels);
    }

    /**
     * マルチcURL実行
     *
     * @return array<string, ResponseEntity>
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function exec(): array {

        if(count($this->channels) === 0)
            throw new InvalidArgumentException('No channels were found');

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

        if(!$running || $result !== MultiCurlError::OK->value)
            throw new RuntimeException('The request could not be started. One of the settings in the multi-request may be invalid.');

        // 返却用
        $ret = [];

        // select前に全ての処理が終わっていたりすると複数の結果が入っていることがあるのでループが必要
        do switch(curl_multi_select($this->cmh, $this->timeoutSec)){
            // selectに失敗
            case -1:
                // ちょっと待ってから
                usleep(10);

                // 再実行
                $executor($running);

                continue 2;

            // タイムアウト
            case 0:
                continue 2;

            // どれかが成功 or 失敗
            default:
                // ステータスを更新
                $executor($running);

                // 変化のあったcurlハンドラーを取得する
                do if($raised = curl_multi_info_read($this->cmh, $remains)){
                    // 結果が返ってきたハンドラー
                    $ch = $raised['handle'];

                    $responseEntity = new ResponseEntity();

                    $responseEntity->id  = array_search($ch, $curlInfoHandler) ?: 'not found';
                    $responseEntity->url = $curlInfoUrl[$responseEntity->id] ?? '';

                    $curlResult = curl_multi_getcontent($ch);

                    // cURLのレスポンスメタ情報をセット
                    $this->setCurlInfoMeta($ch, $responseEntity);

                    // ReturnTransfer無効時、またはcURL失敗時
                    if($curlResult === null){
                        $responseEntity->result = false;

                        $responseEntity->responseHeader = null;
                        $responseEntity->responseBody   = null;

                        $responseEntity->errorEnum    = CurlError::from(curl_errno($ch));
                        $responseEntity->errorMessage = curl_error($ch);
                    }
                    // ReturnTransfer有効、且つcURL成功時
                    else{
                        $responseEntity->result = true;

                        // ヘッダー情報とボディー情報に分割
                        $this->divideContent($curlResult, $responseEntity);

                        $responseEntity->errorEnum    = CurlError::OK;
                        $responseEntity->errorMessage = '';
                    }

                    $ret[$responseEntity->id] = $responseEntity;

                    // レスポンスが受け取れたハンドラーは除去
                    curl_multi_remove_handle($this->cmh, $ch);
                } while($remains);
        } while($running);

        return $ret;
    }
}