<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Factory;

use CURLFile;
use Ennacx\SimpleCurl\Entity\Config\CurlOptionsApplierImpl;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\PreparedRequest;
use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Enum\ContentType;
use Ennacx\SimpleCurl\Static\HeaderUtils;
use LogicException;

/**
 * PreparedRequestをcURLオプション配列へ変換するFactory。
 *
 * Request本体、CurlOptions、各Configを集約し、`curl_setopt_array()` に渡せる形式へ変換する。
 */
final class CurlOptionsFactory {

    /**
     * PreparedRequest本体と各Configから `curl_setopt_array()` 用の配列を生成する。
     *
     * レスポンスボディまたはヘッダーを取得する場合のみ `CURLOPT_RETURNTRANSFER` を有効にする。
     * 送信ヘッダーはRequestのヘッダーとConfigが追加したヘッダーを統合して設定する。
     *
     * @param  PreparedRequest   $preparedRequest
     * @return array<int, mixed>
     */
    public function fromPreparedRequest(PreparedRequest $preparedRequest): array {

        $curlOptions = $preparedRequest->options ?? CurlOptions::create();

        // GETクエリ付与
        $url = $preparedRequest->request->url;
        if(!empty($preparedRequest->request->queryParams)){
            $url .= '?' . http_build_query($preparedRequest->request->queryParams);
        }

        // フラグメント付与 (URLの仕様上、必ずGETクエリの後にすること)
        if(isset($preparedRequest->request->fragment)){
            $url .= '#' . $preparedRequest->request->fragment;
        }

        // 基本設定
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => ($curlOptions->captureBody || $curlOptions->captureHeaders),
            CURLOPT_HEADER         => $curlOptions->captureHeaders,
        ];

        // HTTPメソッド設定の追加
        $options += $preparedRequest->request->method->toCurlOptions();
        $headers  = $preparedRequest->request->requestHeaders;

        // リクエストボディの付与
        if($preparedRequest->request->requestBody !== null || $preparedRequest->request->attachments !== []){
            $body = $this->buildPostFields($preparedRequest->request);
            if($body !== null){
                $options[CURLOPT_POSTFIELDS] = $body;
            }

            unset($body);
        }

        // multi-part形式の場合はContent-Typeを削除する (cURL側に任せて強制しない)
        if($preparedRequest->request->attachments !== [] && HeaderUtils::has($headers, 'Content-Type')){
            HeaderUtils::remove($headers, 'Content-Type');
        }
        // ユーザーがContent-Typeを指定していない場合は既定値を付与する
        else if($preparedRequest->request->contentType !== null && $preparedRequest->request->contentType !== ContentType::MultipartFormData){
            if(!HeaderUtils::has($headers, 'Content-Type')){
                $headers['Content-Type'] = $preparedRequest->request->contentType->value;
            }
        }

        // ユーザーがAcceptを指定している場合は設定
        if(!empty($preparedRequest->request->acceptHeaders)){
            if(!HeaderUtils::has($headers, 'Accept')){
                $headers['Accept'] = implode(', ', $preparedRequest->request->acceptHeaders);
            }
        }

        // 各Configの設定内容をcURL形式のオプションに変換して付与
        foreach(array_filter($curlOptions->getConfig(), fn($config): bool => ($config instanceof CurlOptionsApplierImpl)) as $config){
            $config->applyToCurlOptions($options, $headers);
        }

        // オプションにヘッダー情報付与
        if($headers !== []){
            $options[CURLOPT_HTTPHEADER] = $this->formatHeaders($headers);
        }

        return $options;
    }

    /**
     * Requestに保持されたボディ情報を `CURLOPT_POSTFIELDS` へ渡せる形式へ変換する。
     *
     * 添付ファイルがある場合はmultipart/form-data用の配列を生成し、
     * 添付ファイルがない場合はContent-Typeに応じて文字列ボディを生成する。
     *
     * @param  Request $request
     * @return string|array|null
     */
    private function buildPostFields(Request $request): string|array|null {

        if(!empty($request->attachments)){
            return $this->buildMultipart($request);
        }

        if($request->requestBody === null){
            return null;
        }

        return match($request->requestBody->contentType){
            ContentType::Json => json_encode(
                $request->requestBody->body,
                $request->requestBody->options['flags'] ?? JSON_UNESCAPED_SLASHES,
            ) ?: null,

            ContentType::FormUrlEncoded => http_build_query(
                $request->requestBody->body,
            ),

            default => (is_array($request->requestBody->body)) ?
                throw new LogicException('Array body requires an encodable content type.') :
                $request->requestBody->body,
        };
    }

    /**
     * 添付ファイルとフォーム項目をmultipart/form-data用の配列へ変換する。
     *
     * cURLは配列とCURLFileを受け取るとboundary付きContent-Typeを生成するため、
     * 呼び出し元ではユーザー指定のContent-Typeヘッダーを削除する。
     *
     * @param  Request $request
     * @return array<string, mixed>
     */
    private function buildMultipart(Request $request): array {

        $fields = [];

        $body = $request->requestBody;

        if($body !== null){
            if(!is_array($body->body)){
                throw new LogicException('Multipart form fields must be an array.');
            }
            if($body->contentType !== ContentType::FormUrlEncoded){
                throw new LogicException('Attachments can only be combined with form fields.');
            }

            $fields = $body->body;
        }

        $overwrite = $body?->options['overwrite'] ?? true;

        foreach($request->attachments as $attachment){
            if(array_key_exists($attachment->name, $fields) && !$overwrite){
                continue;
            }

            $fields[$attachment->name] = new CURLFile(
                $attachment->path,
                $attachment->mimeType,
                $attachment->filename,
            );
        }

        return $fields;
    }

    /**
     * ヘッダー連想配列をcURLが受け取れる "Name: value" 形式へ変換する。
     *
     * @param  array<string, string> $headers
     * @return string[]
     */
    private function formatHeaders(array $headers): array {

        $ret = [];
        foreach($headers as $name => $value){
            $ret[] = (is_string($name)) ? sprintf('%s: %s', $name, $value) : $value;
        }

        return $ret;
    }
}
