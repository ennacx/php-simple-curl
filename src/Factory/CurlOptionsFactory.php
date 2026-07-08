<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Factory;

use Ennacx\SimpleCurl\Entity\Config\CurlOptionsApplierImpl;
use Ennacx\SimpleCurl\Entity\Request;

/**
 * RequestをcURLオプション配列へ変換するFactory。
 */
final class CurlOptionsFactory {

    /**
     * Request本体と各Configからcurl_setopt_array()用の配列を生成する。
     *
     * @param  Request $request
     * @return array<int, mixed>
     */
    public function fromRequest(Request $request): array {

        // 基本設定
        $options = [
            CURLOPT_URL            => $request->url,
            CURLOPT_RETURNTRANSFER => ($request->captureBody || $request->captureHeaders),
            CURLOPT_HEADER         => $request->captureHeaders,
        ];

        // HTTPメソッド設定の追加
        $options += $request->method->toCurlOptions();
        $headers  = $request->requestHeaders;

        foreach(array_filter($request->getConfig(), fn($config): bool => $config instanceof CurlOptionsApplierImpl) as $config){
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
