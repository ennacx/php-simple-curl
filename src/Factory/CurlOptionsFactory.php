<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Factory;

use Ennacx\SimpleCurl\Entity\Config\CurlOptionsApplierImpl;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\PreparedRequest;
use Ennacx\SimpleCurl\Static\HeaderUtils;

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
        if($preparedRequest->request->requestBody !== null){
            $options[CURLOPT_POSTFIELDS] = $preparedRequest->request->requestBody;
        }

        // ユーザーがContent-Typeを指定していない場合は既定値を付与する
        if($preparedRequest->request->contentType !== null){
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
