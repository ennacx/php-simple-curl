<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * cURL proxy protocols.
 */
enum ProxyProtocol implements ToCurlConstInterface {

    /** HTTP proxy. */
    case HTTP;

    /** SOCKS5 proxy. */
    case SOCKS5;

    /**
     * @inheritDoc
     */
    public function toCurlConst(): int {
        return match($this){
            self::HTTP   => CURLPROXY_HTTP,
            self::SOCKS5 => CURLPROXY_SOCKS5
        };
    }

    /**
     * Returns the default port for the protocol.
     *
     * @return int
     */
    public function defaultPort(): int {
        return match($this){
            self::HTTP   => 80,
            self::SOCKS5 => 1080
        };
    }
}
