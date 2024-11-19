<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Trait;

use CurlHandle;
use Ennacx\SimpleCurl\Entity\ResponseEntity;

/**
 * Lib共通使用トレイト
 */
trait CurlLibTrait {

    /**
     * cURLのレスポンスメタ情報をセット
     *
     * @param  CurlHandle     $ch
     * @param  ResponseEntity $entity
     * @return void
     */
    public function setCurlInfoMeta(CurlHandle $ch, ResponseEntity $entity): void {

        $temp = curl_getinfo($ch);
        if(is_array($temp))
            $entity->setInfo($temp);
    }

    /**
     * ヘッダー情報とボディー情報に分割してセット
     *
     * @param  string|bool    $curlResult
     * @param  ResponseEntity $entity
     * @return void
     */
    public function divideContent(string|bool $curlResult, ResponseEntity $entity): void {

        if(is_string($curlResult)){
            if($entity->header_size !== null){
                $entity->responseHeader = trim(substr($curlResult, 0, $entity->header_size));
                $entity->responseBody   = substr($curlResult, $entity->header_size);
            } else{
                $entity->responseHeader = null;
                $entity->responseBody   = $curlResult;
            }
        }
    }
}