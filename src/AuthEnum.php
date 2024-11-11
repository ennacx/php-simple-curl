<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

enum AuthEnum {
    /** 認証無し */
    case AUTH_NONE;

    /** 最適な認証方法の自動選択 */
    case AUTH_AUTO;

    /** BASIC認証以外の最適な認証方法の選択 */
    case AUTH_SAFE;

    /** @var int BASIC認証 */
    case AUTH_BASIC;

    /** ダイジェスト認証 */
    case AUTH_DIGEST;

    /** GSS-API認証 (SASL, Kerberos etc SSO) */
    case AUTH_GSS;

    /** Windows NT LAN Manager認証 */
    case AUTH_NTLM;

    /** AWS Signature Version 4 */
    case AUTH_AWSSIG4;

    /**
     * cURL用の定数に変換
     *
     * @return int
     */
    public function toCurlConst(): int {
        return match($this){
            // 認証無し
            self::AUTH_NONE => CURLAUTH_NONE,
            // 最適な認証方法の選択
            self::AUTH_AUTO => CURLAUTH_ANY,
            // BASIC認証以外の最適な認証方法の選択
            self::AUTH_SAFE => CURLAUTH_ANYSAFE,
            // BASIC認証
            self::AUTH_BASIC => CURLAUTH_BASIC,
            // ダイジェスト認証
            self::AUTH_DIGEST => CURLAUTH_DIGEST,
            // GSS-API認証 (SASL, Kerberos etc SSO)
            self::AUTH_GSS => CURLAUTH_GSSNEGOTIATE,
            // Windows NT LAN Manager認証
            self::AUTH_NTLM => CURLAUTH_NTLM,
            // AWS Signature Version 4
            self::AUTH_AWSSIG4 => CURLAUTH_AWS_SIGV4
        };
    }
}
