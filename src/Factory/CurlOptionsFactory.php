<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Factory;

use Ennacx\SimpleCurl\Entity\Config\CurlOptionsApplierImpl;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\ConfiguredRequest;

/**
 * ConfiguredRequestをcURLオプション配列へ変換するFactory。
 *
 * Request本体、CurlOptions、各Configを集約し、`curl_setopt_array()` に渡せる形式へ変換する。
 */
final class CurlOptionsFactory {

    /**
     * ConfiguredRequest本体と各Configから `curl_setopt_array()` 用の配列を生成する。
     *
     * レスポンスボディまたはヘッダーを取得する場合のみ `CURLOPT_RETURNTRANSFER` を有効にする。
     * 送信ヘッダーはRequestのヘッダーとConfigが追加したヘッダーを統合して設定する。
     *
     * @param  ConfiguredRequest $configuredRequest
     * @return array<int, mixed>
     */
    public function fromConfiguredRequest(ConfiguredRequest $configuredRequest): array {

        $curlOptions = $configuredRequest->options ?? CurlOptions::create();

        // GETクエリ付与
        $url = $configuredRequest->request->url;
        if(!empty($configuredRequest->request->queryParams)){
            $url .= '?' . http_build_query($configuredRequest->request->queryParams);
        }

        // フラグメント付与 (URLの仕様上、必ずGETクエリの後にすること)
        if(isset($configuredRequest->request->fragment)){
            $url .= '#' . $configuredRequest->request->fragment;
        }

        // 基本設定
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => ($curlOptions->captureBody || $curlOptions->captureHeaders),
            CURLOPT_HEADER         => $curlOptions->captureHeaders,
        ];

        // HTTPメソッド設定の追加
        $options += $configuredRequest->request->method->toCurlOptions();
        $headers  = $configuredRequest->request->requestHeaders;

        // リクエストボディの付与
        if($configuredRequest->request->requestBody !== null){
            $options[CURLOPT_POSTFIELDS] = $configuredRequest->request->requestBody;
        }

        // ユーザーがContent-Typeを指定していない場合は既定値を付与する
        if($configuredRequest->request->requestContentType !== null){
            if(!$this->hasHeader($headers, 'content-type')){
                $headers['Content-Type'] = $configuredRequest->request->requestContentType->getContentType();
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

    private function hasHeader(array $headers, string $name): bool {

        $needle = strtolower($name);

        foreach($headers as $key => $value){
            if(is_string($key) && strtolower($key) === $needle){
                return true;
            }

            if(is_string($value) && str_starts_with(strtolower($value), "{$needle}:")){
                return true;
            }
        }

        return false;
    }
}
