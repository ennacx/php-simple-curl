<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Client;

use Ennacx\SimpleCurl\Entity\PendingRequest;
use Ennacx\SimpleCurl\Entity\Response;
use Ennacx\SimpleCurl\Factory\CurlOptionsFactory;
use Ennacx\SimpleCurl\Factory\ResponseFactory;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 単一のPendingRequestをcURLで実行するクライアント。
 *
 * cURLハンドラーの生成、オプション適用、実行、Response生成、ハンドラー解放までを担当する。
 */
final readonly class SingleClient {

    /**
     * コンストラクタ
     *
     * @param CurlOptionsFactory $optionsFactory PendingRequestからcURLオプションを生成するFactory
     * @param ResponseFactory    $responseFactory cURL実行結果からResponseを生成するFactory
     */
    public function __construct(
        private CurlOptionsFactory $optionsFactory = new CurlOptionsFactory(),
        private ResponseFactory $responseFactory = new ResponseFactory(),
    ){
    }

    /**
     * PendingRequestを実行してResponseを返す。
     *
     * 実行時に作成したcURLハンドラーは、成功・失敗に関わらずメソッド内で解放する。
     *
     * @param  PendingRequest $pendingRequest 実行対象のPendingRequest
     * @return Response
     * @throws Throwable
     */
    public function send(PendingRequest $pendingRequest): Response {

        $request = $pendingRequest->request;

        $ch = curl_init($request->url);

        if($ch === false){
            throw new RuntimeException('cURL initialize failed.');
        }

        try{
            if(!curl_setopt_array($ch, $this->optionsFactory->fromPendingRequest($pendingRequest))){
                throw new InvalidArgumentException('Invalid cURL option or value included.');
            }

            $raw = curl_exec($ch);

            return $this->responseFactory->fromCurlResult($ch, $raw, $pendingRequest);
        } catch(Throwable $e){
            throw $e;
        } finally{
            curl_close($ch);
        }
    }
}
