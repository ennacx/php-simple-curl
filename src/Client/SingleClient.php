<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Client;

use Ennacx\SimpleCurl\Entity\ConfiguredRequest;
use Ennacx\SimpleCurl\Entity\Response;
use Ennacx\SimpleCurl\Factory\CurlOptionsFactory;
use Ennacx\SimpleCurl\Factory\ResponseFactory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 単一のConfiguredRequestをcURLで実行するクライアント。
 *
 * cURLハンドラーの生成、オプション適用、実行、Response生成、ハンドラー解放までを担当する。
 */
final readonly class SingleClient {

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
     * ConfiguredRequestを実行してResponseを返す。
     *
     * 実行時に作成したcURLハンドラーは、成功・失敗に関わらずメソッド内で解放する。
     *
     * @param  ConfiguredRequest $configuredRequest 実行対象のConfiguredRequest
     * @return Response
     * @throws Throwable
     */
    public function send(ConfiguredRequest $configuredRequest): Response {

        $ch = curl_init();

        if($ch === false){
            throw new RuntimeException('cURL initialize failed.');
        }

        if(!curl_setopt_array($ch, $this->optionsFactory->fromConfiguredRequest($configuredRequest))){
            throw new InvalidArgumentException('Invalid cURL option or value included.');
        }

        $raw = curl_exec($ch);

        // PHP 8.0以降、CurlHandle はオブジェクトとして管理されるため、curl_close() は呼ばず、スコープアウト時のGCに任せる

        return $this->responseFactory->fromCurlResult($ch, $raw, $configuredRequest);
    }
}
