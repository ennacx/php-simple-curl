<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * cURL HTTP authentication modes.
 */
enum CurlAuth implements ToCurlConstInterface {

    /** No authentication. */
    case NONE;

    /** Let cURL choose any supported authentication method. */
    case AUTO;

    /** Let cURL choose a safe authentication method other than Basic. */
    case SAFE;

    /** Basic authentication. */
    case BASIC;

    /** Digest authentication. */
    case DIGEST;

    /** GSS-API authentication. */
    case GSS;

    /** Windows NTLM authentication. */
    case NTLM;

    /** AWS Signature Version 4. */
    case AWSSIG4;

    /**
     * @inheritDoc
     */
    public function toCurlConst(): int {
        return match($this){
            self::NONE    => CURLAUTH_NONE,
            self::AUTO    => CURLAUTH_ANY,
            self::SAFE    => CURLAUTH_ANYSAFE,
            self::BASIC   => CURLAUTH_BASIC,
            self::DIGEST  => CURLAUTH_DIGEST,
            self::GSS     => CURLAUTH_GSSNEGOTIATE,
            self::NTLM    => CURLAUTH_NTLM,
            self::AWSSIG4 => CURLAUTH_AWS_SIGV4
        };
    }
}
