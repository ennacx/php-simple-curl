<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * cURL proxy authentication modes.
 */
enum ProxyAuth implements ToCurlConst {

    /** No authentication. */
    case NONE;

    /** Basic authentication. */
    case BASIC;

    /** Windows NTLM authentication. */
    case NTLM;

    /**
     * @inheritDoc
     */
    public function toCurlConst(): int {
        return match($this){
            self::NONE  => CURLAUTH_NONE,
            self::BASIC => CURLAUTH_BASIC,
            self::NTLM  => CURLAUTH_NTLM
        };
    }
}
