<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Factory;

use CURLFile;
use Ennacx\SimpleCurl\Entity\Config\CurlOptionsApplierInterface;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\PreparedRequest;
use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Enum\ContentType;
use Ennacx\SimpleCurl\Exception\InvalidRequestException;
use Ennacx\SimpleCurl\Static\HeaderUtils;
use LogicException;

/**
 * Builds cURL options from a prepared request.
 */
final class CurlOptionsFactory {

    /**
     * Converts a prepared request into options for curl_setopt_array().
     *
     * @param  PreparedRequest $preparedRequest Prepared request.
     * @return array<int, mixed>
     * @throws InvalidRequestException
     */
    public function fromPreparedRequest(PreparedRequest $preparedRequest): array {

        $request     = $preparedRequest->getRequest();
        $curlOptions = $preparedRequest->getOptions() ?? CurlOptions::create();

        // 基本設定
        $options = [
            CURLOPT_URL            => $this->buildUrl($preparedRequest),
            CURLOPT_RETURNTRANSFER => ($curlOptions->isCapturingBody() || $curlOptions->isCapturingHeaders()),
            CURLOPT_HEADER         => $curlOptions->isCapturingHeaders(),
        ];

        // HTTPメソッド設定の追加
        $options += $request->getMethod()->toCurlOptions();
        $headers  = $request->getHeaders();

        // リクエストボディの付与
        if($request->getRequestBody() !== null || $request->getAttachmentEntries() !== []){
            $body = $this->buildPostFields($request);
            if($body !== null){
                $options[CURLOPT_POSTFIELDS] = $body;
            }

            unset($body);
        }

        // multipart形式の場合はContent-Typeを削除する (cURL側に任せて指定させない)
        if($request->getAttachmentEntries() !== [] && HeaderUtils::has($headers, 'Content-Type')){
            HeaderUtils::remove($headers, 'Content-Type');
        }
        // ユーザーがContent-Typeを指定していない場合は既定値を付与する
        else if(!in_array($request->getContentType(), [null, ContentType::MultipartFormData], true)){
            if(!HeaderUtils::has($headers, 'Content-Type')){
                $headers['Content-Type'] =
                    $request->getContentType()?->value ??
                    throw new InvalidRequestException('Invalid Content-Type');
            }
        }

        // ユーザーがAcceptを指定している場合は設定
        if(!empty($request->getAcceptHeaders())){
            if(!HeaderUtils::has($headers, 'Accept')){
                $headers['Accept'] = implode(', ', $request->getAcceptHeaders());
            }
        }

        // 各Configの設定内容をcURL形式のオプションに変換して付与
        foreach(array_filter($curlOptions->getConfig(), fn($config): bool => ($config instanceof CurlOptionsApplierInterface)) as $config){
            $config->applyToCurlOptions($options, $headers);
        }

        // オプションにヘッダー情報付与
        if($headers !== []){
            $options[CURLOPT_HTTPHEADER] = $this->formatHeaders($headers);
        }

        return $options;
    }

    /**
     * PreparedRequestの内容から最終的なURLを再構築する。
     *
     * @param  PreparedRequest $preparedRequest
     * @return string
     */
    private function buildUrl(PreparedRequest $preparedRequest): string {

        $request = $preparedRequest->getRequest();

        // GETクエリ付与
        $url = $request->getUrl();
        if(!empty($request->getQueryParams())){
            $url .= '?' . http_build_query($request->getQueryParams());
        }

        // フラグメント付与 (URLの仕様上、必ずGETクエリの後にすること)
        if($request->getFragment() !== null){
            $url .= '#' . $request->getFragment();
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

        if(!empty($request->getAttachmentEntries())){
            return $this->buildMultipart($request);
        }

        $requestBody = $request->getRequestBody();

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

        $requestBody = $request->getRequestBody();

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

        foreach($request->getAttachmentEntries() as $attachmentEntry){
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
