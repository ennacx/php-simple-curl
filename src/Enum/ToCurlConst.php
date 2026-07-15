<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * Converts enum cases into cURL constants.
 */
interface ToCurlConst {

    /**
     * Returns the cURL constant value for the enum case.
     *
     * @return int
     */
    public function toCurlConst(): int;
}
