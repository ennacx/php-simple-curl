<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Trait;

use CurlHandle;
use Ennacx\SimpleCurl\Entity\ResponseEntity;
use Ennacx\SimpleCurl\Enum\CurlError;
use RuntimeException;

/**
 * Lib共通使用トレイト
 */
trait CurlLibTrait {

    /**
     * レスポンスエンティティー作成
     *
     * @return ResponseEntity
     */
    public function newResponseEntity(): ResponseEntity {
        return new ResponseEntity();
    }

    /**
     * cURLのレスポンスメタ情報をセット
     *
     * @param  CurlHandle     $ch
     * @param  ResponseEntity $entity
     * @return void
     */
    public function setCurlInfoMeta(CurlHandle $ch, ResponseEntity $entity): void {

        $temp = curl_getinfo($ch);
        if(is_array($temp)){
            $entity->setInfo($temp);
            $entity->setTime();
        }
    }

    /**
     * cURLの実行結果、レスポンス、エラーをエンティティーにセット
     *
     * @param  bool|string|null                                                      $curlResult   ```curl_exec()```, ```curl_multi_getcontent()``` の実行結果
     * @param  ResponseEntity                                                        $entity       格納エンティティー
     * @param  bool|null                                                             $divideHeader レスポンスヘッダー分割フラグ
     * @param  array{ continuableErrorCodes: int[], retries: int, throw: bool }|null $option       Single側設定
     * @return boolean
     */
    public function setResponseToEntity(bool|null|string $curlResult, ResponseEntity $entity, ?bool $divideHeader = null, ?array $option = null): bool {

        if($option !== null)
            extract($option);

        // ReturnTransfer無効時、またはcURL失敗時 (curl_multi_getcontent()ではCURLOPT_RETURNTRANSFER未設定時にnull)
        if(is_bool($curlResult) || is_null($curlResult)){
            $entity->result = $curlResult ?? false;

            $entity->responseHeader = null;
            $entity->responseBody   = null;

            // cURL失敗時はエラー情報を格納
            if(!$entity->result){
                if(!in_array(curl_errno($this->ch), $continuableErrorCodes ?? [], true) || ($retries ?? 1) === 0){
                    $entity->errorEnum    = CurlError::tryFrom(curl_errno($this->ch)) ?? CurlError::OTHER;
                    $entity->errorMessage = curl_error($this->ch);

                    if($throw ?? false)
                        throw new RuntimeException(sprintf('cURL error (Code: %d): %s', $entity->errorEnum->value, $entity->errorMessage));
                    else
                        return true;
                }
            } else{
                $entity->errorEnum    = CurlError::OK;
                $entity->errorMessage = '';

                return true;
            }
        }
        // ReturnTransfer有効、且つcURL成功時
        else if(is_string($curlResult)){
            $entity->result = true;

            $entity->errorEnum    = CurlError::OK;
            $entity->errorMessage = '';

            // ヘッダー情報を返却要請している場合はヘッダー情報とボディー情報に分割
            if($divideHeader === true)
                $this->divideContent($curlResult, $entity);
            // 要請していない場合は全部ボディ
            else
                $entity->responseBody = $curlResult;

            return true;
        }
        // 理論上ここには入らない
        else{
            throw new RuntimeException('Unknown error.');
        }

        return false;
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