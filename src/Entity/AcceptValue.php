<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

/**
 * Acceptヘッダーへ変換できる値を表すインターフェース。
 */
interface AcceptValue {

    /**
     * `Accept` ヘッダーに付与する値を返す。
     *
     * @return string
     */
    public function toHeaderValue(): string;
}
