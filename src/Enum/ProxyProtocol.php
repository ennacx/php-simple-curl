<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * cURL プロキシープロトコル
 */
enum ProxyProtocol {

    /** HTTPプロトコル */
    case HTTP;

    /** SOCKSプロトコル v5 */
    case SOCKS5;

    /**
     * cURL用の定数に変換
     *
     * @return int
     */
    public function toCurlConst(): int {
        return match($this){
            // HTTPプロトコル
            self::HTTP => CURLPROXY_HTTP,
            // SOCKSプロトコル v5
            self::SOCKS5 => CURLPROXY_SOCKS5
        };
    }
}