<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * cURL プロキシー認証
 */
enum ProxyAuth {

    /** 認証無し */
    case NONE;

    /** @var int BASIC認証 */
    case BASIC;

    /** Windows NT LAN Manager認証 */
    case NTLM;

    /**
     * cURL用の定数に変換
     *
     * @return int
     */
    public function toCurlConst(): int {
        return match($this){
            // 認証無し
            self::NONE => CURLAUTH_NONE,
            // BASIC認証
            self::BASIC => CURLAUTH_BASIC,
            // Windows NT LAN Manager認証
            self::NTLM => CURLAUTH_NTLM
        };
    }
}
