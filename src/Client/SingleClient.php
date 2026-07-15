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
 * Sends a single HTTP request through cURL.
 *
 * The client builds cURL options, executes the handle, and converts the result
 * into a Response object.
 */
final readonly class SingleClient {

    /**
     * Creates a single-request client.
     *
     * @param CurlOptionsFactory $optionsFactory  Factory used to build cURL options.
     * @param ResponseFactory    $responseFactory Factory used to create response objects.
     */
    public function __construct(
        private CurlOptionsFactory $optionsFactory  = new CurlOptionsFactory(),
        private ResponseFactory    $responseFactory = new ResponseFactory(),
    ){
    }

    /**
     * Sends a request and returns its response.
     *
     * Plain Request instances are prepared internally with default options.
     *
     * @param  Request|PreparedRequest $preparedRequest Request to send.
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

        // PHP 8.0以降、CurlHandle はオブジェクトとして管理されるため、curl_close() は呼ばずスコープアウト時のGCに任せる

        // 実行結果からResponseを生成
        return $this->responseFactory->fromCurlResult($ch, $raw, $preparedRequest);
    }
}
