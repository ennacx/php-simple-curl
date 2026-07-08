<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Client;

use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Entity\Response;
use Ennacx\SimpleCurl\Factory\CurlOptionsFactory;
use Ennacx\SimpleCurl\Factory\ResponseFactory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 単一のRequestをcURLで実行するクライアント。
 */
final readonly class SingleClient {

    /**
     * コンストラクタ
     *
     * @param CurlOptionsFactory $optionsFactory RequestからcURLオプションを生成するFactory
     * @param ResponseFactory    $responseFactory cURL実行結果からResponseを生成するFactory
     */
    public function __construct(
        private CurlOptionsFactory $optionsFactory = new CurlOptionsFactory(),
        private ResponseFactory $responseFactory = new ResponseFactory(),
    ){
    }

    /**
     * Requestを実行してResponseを返す。
     *
     * @param  Request $request 実行対象のRequest
     * @return Response
     * @throws Throwable
     */
    public function send(Request $request): Response {

        $ch = curl_init($request->url);

        if($ch === false){
            throw new RuntimeException('cURL initialize failed.');
        }

        try{
            if(!curl_setopt_array($ch, $this->buildOptions($request))){
                throw new InvalidArgumentException('Invalid cURL option or value included.');
            }

            $raw = curl_exec($ch);

            return $this->responseFactory->fromCurlResult($ch, $raw, $request);
        } catch(Throwable $e){
            throw $e;
        } finally{
            curl_close($ch);
        }
    }

    /**
     * RequestからcURLに渡すオプション配列を生成する。
     *
     * @param  Request $request
     * @return array<int, mixed>
     */
    private function buildOptions(Request $request): array {
        return $this->optionsFactory->fromRequest($request);
    }
}
