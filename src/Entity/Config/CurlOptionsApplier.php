<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

/**
 * Configが自身の責務範囲のcURLオプションを適用するためのインターフェース。
 */
interface CurlOptionsApplier {

    /**
     * `curl_setopt_array()`へ渡すオプション配列と送信ヘッダー配列へ設定を反映する。
     *
     * @param  array<int, mixed>     $options
     * @param  array<string, string> $headers
     * @return void
     */
    public function applyToCurlOptions(array &$options, array &$headers): void;
}
