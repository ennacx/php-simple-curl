<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Factory;

use Ennacx\SimpleCurl\Entity\Config\CurlOptionsApplierImpl;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\PendingRequest;
use InvalidArgumentException;

/**
 * PendingRequestをcURLオプション配列へ変換するFactory。
 *
 * Request本体、CurlOptions、各Configを集約し、`curl_setopt_array()` に渡せる形式へ変換する。
 */
final class CurlOptionsFactory {

    /**
     * PendingRequest本体と各Configから `curl_setopt_array()` 用の配列を生成する。
     *
     * レスポンスボディまたはヘッダーを取得する場合のみ `CURLOPT_RETURNTRANSFER` を有効にする。
     * 送信ヘッダーはRequestのヘッダーとConfigが追加したヘッダーを統合して設定する。
     *
     * @param  PendingRequest $pendingRequest
     * @return array<int, mixed>
     */
    public function fromPendingRequest(PendingRequest $pendingRequest): array {

        $curlOptions = $pendingRequest->options ?? CurlOptions::create();

        // GETパラメーター付与
        $url = $pendingRequest->request->url;
        if(!empty($pendingRequest->request->queryParams)){
            // フラグメント
            $fragment = null;
            if(str_contains($url, '#')){
                $temp = explode('#', $url);
                if(count($temp) !== 2){
                    throw new InvalidArgumentException(sprintf('Invalid URL: %s', $url));
                }

                $url      = $temp[0];
                $fragment = $temp[1];

                unset($temp);
            }

            $url .= '?' . http_build_query($pendingRequest->request->queryParams);
            if(isset($fragment)){
                $url .= '#' . $fragment;
            }
        }

        // 基本設定
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => ($curlOptions->captureBody || $curlOptions->captureHeaders),
            CURLOPT_HEADER         => $curlOptions->captureHeaders,
        ];

        // HTTPメソッド設定の追加
        $options += $pendingRequest->request->method->toCurlOptions();
        $headers  = $pendingRequest->request->requestHeaders;

        foreach(array_filter($curlOptions->getConfig(), fn($config): bool => $config instanceof CurlOptionsApplierImpl) as $config){
            $config->applyToCurlOptions($options, $headers);
        }

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
            $ret[] = sprintf('%s: %s', $name, $value);
        }

        return $ret;
    }
}
