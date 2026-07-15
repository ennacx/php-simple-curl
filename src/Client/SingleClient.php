<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Client;

use Ennacx\SimpleCurl\Entity\PreparedRequest;
use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Entity\Response;
use Ennacx\SimpleCurl\Exception\CurlExecutionException;
use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;
use Ennacx\SimpleCurl\Factory\CurlOptionsFactory;
use Ennacx\SimpleCurl\Factory\ResponseFactory;

/**
 * 単一のリクエストをcURLで実行するクライアント。
 *
 * cURLハンドラーの生成、オプション適用、実行、Response生成、ハンドラー解放までを担当する。
 */
final readonly class SingleClient {

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
     * Requestを実行してResponseを返す。
     *
     * 実行時に作成したcURLハンドラーは、成功・失敗に関わらずメソッド内で解放する。
     *
     * @param  Request|PreparedRequest $preparedRequest 実行対象のRequestまたはPreparedRequest
     * @return Response
     * @throws InvalidConfigurationException
     * @throws CurlExecutionException
     */
    public function send(Request|PreparedRequest $preparedRequest): Response {

        $ch = curl_init();

        if($ch === false){
            throw new CurlExecutionException('cURL initialize failed.');
        }

        // Requestの変換
        if($preparedRequest instanceof Request){
            $preparedRequest = $preparedRequest->prepare(options: null);
        }

        // `CURLOPT_*` の設定
        if(!curl_setopt_array($ch, $this->optionsFactory->fromPreparedRequest($preparedRequest))){
            throw new InvalidConfigurationException('Invalid cURL option or value included.');
        }

        // cURL実行
        $raw = curl_exec($ch);

        // PHP 8.0以降、CurlHandle はオブジェクトとして管理されるため、curl_close() は呼ばず、スコープアウト時のGCに任せる

        // 実行結果からResponseを生成
        return $this->responseFactory->fromCurlResult($ch, $raw, $preparedRequest);
    }
}
