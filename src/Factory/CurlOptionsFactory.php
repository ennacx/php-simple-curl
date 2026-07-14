<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Factory;

use CURLFile;
use Ennacx\SimpleCurl\Entity\Config\CurlOptionsApplier;
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

        $request     = $preparedRequest->request;
        $curlOptions = $preparedRequest->options ?? CurlOptions::create();

        // 基本設定
        $options = [
            CURLOPT_URL            => $this->buildUrl($preparedRequest),
            CURLOPT_RETURNTRANSFER => ($curlOptions->captureBody || $curlOptions->captureHeaders),
            CURLOPT_HEADER         => $curlOptions->captureHeaders,
        ];

        // HTTPメソッド設定の追加
        $options += $request->method->toCurlOptions();
        $headers  = $request->requestHeaders;

        // リクエストボディの付与
        if($request->requestBody !== null || $request->attachmentEntries !== []){
            $body = $this->buildPostFields($request);
            if($body !== null){
                $options[CURLOPT_POSTFIELDS] = $body;
            }

            unset($body);
        }

        // multi-part形式の場合はContent-Typeを削除する (cURL側に任せて指定させない)
        if($request->attachmentEntries !== [] && HeaderUtils::has($headers, 'Content-Type')){
            HeaderUtils::remove($headers, 'Content-Type');
        }
        // ユーザーがContent-Typeを指定していない場合は既定値を付与する
        else if(!in_array($request->contentType, [null, ContentType::MultipartFormData], true)){
            if(!HeaderUtils::has($headers, 'Content-Type')){
                $headers['Content-Type'] = $request->contentType->value;
            }
        }

        // ユーザーがAcceptを指定している場合は設定
        if(!empty($request->acceptHeaders)){
            if(!HeaderUtils::has($headers, 'Accept')){
                $headers['Accept'] = implode(', ', $request->acceptHeaders);
            }
        }

        // 各Configの設定内容をcURL形式のオプションに変換して付与
        foreach(array_filter($curlOptions->getConfig(), fn($config): bool => ($config instanceof CurlOptionsApplier)) as $config){
            $config->applyToCurlOptions($options, $headers);
        }

        // オプションにヘッダー情報付与
        if($headers !== []){
            $options[CURLOPT_HTTPHEADER] = $this->formatHeaders($headers);
        }

        return $options;
    }

    /**
     * PreparedRequestの内容からURLを生成する。
     *
     * @param  PreparedRequest $preparedRequest
     * @return string
     */
    private function buildUrl(PreparedRequest $preparedRequest): string {

        $request = $preparedRequest->request;

        // GETクエリ付与
        $url = $request->url;
        if(!empty($request->queryParams)){
            $url .= '?' . http_build_query($request->queryParams);
        }

        // フラグメント付与 (URLの仕様上、必ずGETクエリの後にすること)
        if(isset($request->fragment)){
            $url .= '#' . $request->fragment;
        }

        return $url;
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

        if(!empty($request->attachmentEntries)){
            return $this->buildMultipart($request);
        }

        $requestBody = $request->requestBody;

        if($requestBody === null){
            return null;
        }

        // リクエスト時のContent-Typeからbodyの変換方法を決定する
        return match($requestBody->contentType){
            ContentType::Json => json_encode(
                $requestBody->body,
                $requestBody->options['flags'] ?? JSON_UNESCAPED_SLASHES,
            ) ?: null,

            ContentType::FormUrlEncoded => http_build_query(
                $requestBody->body,
            ),

            default => (is_array($requestBody->body)) ?
                throw new LogicException('Array body requires an encodable content type.') :
                $requestBody->body,
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

        $requestBody = $request->requestBody;

        // 添付ファイル以外にボディーが設定されている場合
        if($requestBody !== null){
            // ボディーは配列であることが必須
            if(!is_array($requestBody->body)){
                throw new LogicException('Multipart form fields must be an array.');
            }
            // フォームのContent-Typeであること
            if($requestBody->contentType !== ContentType::FormUrlEncoded){
                throw new LogicException('Attachments can only be combined with form fields.');
            }

            // 配列の本文を設定
            $fields = $requestBody->body;
        }

        foreach($request->attachmentEntries as $attachmentEntry){
            $attachment = $attachmentEntry->attachment;
            $overwrite  = $attachmentEntry->allowOverwrite;

            // NOTE: Request側で避けているので理論上入らないが保険として
            if(array_key_exists($attachment->name, $fields) && !$overwrite){
                continue;
            }

            // 添付ファイルを追加
            $fields[$attachment->name] = new CURLFile(
                filename:        $attachment->path,
                mime_type:       $attachment->mimeType,
                posted_filename: $attachment->filename,
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
