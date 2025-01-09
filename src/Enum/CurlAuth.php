<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * cURL認証
 */
enum CurlAuth {

    /** 認証無し */
    case NONE;

    /** 最適な認証方法の自動選択 */
    case AUTO;

    /** BASIC認証以外の最適な認証方法の選択 */
    case SAFE;

    /** @var int BASIC認証 */
    case BASIC;

    /** ダイジェスト認証 */
    case DIGEST;

    /** GSS-API認証 (SASL, Kerberos etc SSO) */
    case GSS;

    /** Windows NT LAN Manager認証 */
    case NTLM;

    /** AWS Signature Version 4 */
    case AWSSIG4;

    /**
     * cURL用の定数に変換
     *
     * @return int
     */
    public function toCurlConst(): int {
        return match($this){
            // 認証無し
            self::NONE => CURLAUTH_NONE,
            // 最適な認証方法の選択
            self::AUTO => CURLAUTH_ANY,
            // BASIC認証以外の最適な認証方法の選択
            self::SAFE => CURLAUTH_ANYSAFE,
            // BASIC認証
            self::BASIC => CURLAUTH_BASIC,
            // ダイジェスト認証
            self::DIGEST => CURLAUTH_DIGEST,
            // GSS-API認証 (SASL, Kerberos etc SSO)
            self::GSS => CURLAUTH_GSSNEGOTIATE,
            // Windows NT LAN Manager認証
            self::NTLM => CURLAUTH_NTLM,
            // AWS Signature Version 4
            self::AWSSIG4 => CURLAUTH_AWS_SIGV4
        };
    }
}
