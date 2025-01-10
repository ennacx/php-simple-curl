<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

interface ToCurlConstImpl {

    /**
     * cURL用の定数に変換
     *
     * @return int
     */
    public function toCurlConst(): int;
}