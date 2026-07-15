<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * cURL SSL/TLS version options.
 */
enum SSLVersion implements ToCurlConst {

    /** Let cURL choose the default version. */
    case DEFAULT;

    /** TLS 1.0. */
    case TLSv1_0;

    /** TLS 1.1. */
    case TLSv1_1;

    /** TLS 1.2. */
    case TLSv1_2;

    /** TLS 1.3. */
    case TLSv1_3;

    /** SSL 2. */
    case SSLv2;

    /** SSL 3. */
    case SSLv3;

    /** Let cURL choose the default maximum version. */
    case MAX_DEFAULT;

    /** Maximum TLS 1.0. */
    case MAX_TLSv1_0;

    /** Maximum TLS 1.1. */
    case MAX_TLSv1_1;

    /** Maximum TLS 1.2. */
    case MAX_TLSv1_2;

    /** Maximum TLS 1.3. */
    case MAX_TLSv1_3;

    /**
     * @inheritDoc
     */
    public function toCurlConst(): int {
        return match($this){
            self::DEFAULT     => CURL_SSLVERSION_DEFAULT,
            self::TLSv1_0     => CURL_SSLVERSION_TLSv1_0,
            self::TLSv1_1     => CURL_SSLVERSION_TLSv1_1,
            self::TLSv1_2     => CURL_SSLVERSION_TLSv1_2,
            self::TLSv1_3     => CURL_SSLVERSION_TLSv1_3,
            self::SSLv2       => CURL_SSLVERSION_SSLv2,
            self::SSLv3       => CURL_SSLVERSION_SSLv3,
            self::MAX_DEFAULT => CURL_SSLVERSION_MAX_DEFAULT,
            self::MAX_TLSv1_0 => CURL_SSLVERSION_MAX_TLSv1_0,
            self::MAX_TLSv1_1 => CURL_SSLVERSION_MAX_TLSv1_1,
            self::MAX_TLSv1_2 => CURL_SSLVERSION_MAX_TLSv1_2,
            self::MAX_TLSv1_3 => CURL_SSLVERSION_MAX_TLSv1_3
        };
    }
}
