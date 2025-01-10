<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * cURL SSLバージョン
 */
enum SSLVersion {

    /** 最適なバージョンを探す */
    case DEFAULT;

//    case TLSv1 = 'TLSv1';

    /** TLSv1.0以上 */
    case TLSv1_0;

    /** TLSv1.1以上 */
    case TLSv1_1;

    /** TLSv1.2以上 */
    case TLSv1_2;

    /** TLSv1.0以上 */
    case TLSv1_3;

    /** SSLv2以上 */
    case SSLv2;

    /** SSLv3以上 */
    case SSLv3;

    /** 最適なバージョンを上限として探す */
    case MAX_DEFAULT;

//    case MAX_NONE = 'MAX_NONE';

    /** TLSv1.0以下 */
    case MAX_TLSv1_0;

    /** TLSv1.1以下 */
    case MAX_TLSv1_1;

    /** TLSv1.2以下 */
    case MAX_TLSv1_2;

    /** TLSv1.3以下 */
    case MAX_TLSv1_3;

    /**
     * cURL用の定数に変換
     *
     * @return int
     */
    public function toCurlConst(): int {
        return match($this){
            // 最適なバージョンを探す
            self::DEFAULT     => CURL_SSLVERSION_DEFAULT,
//            // TLSv1
//            self::TLSv1       => CURL_SSLVERSION_TLSv1,
            // TLSv1.0以上
            self::TLSv1_0     => CURL_SSLVERSION_TLSv1_0,
            // TLSv1.1以上
            self::TLSv1_1     => CURL_SSLVERSION_TLSv1_1,
            // TLSv1.2以上
            self::TLSv1_2     => CURL_SSLVERSION_TLSv1_2,
            // TLSv1.3以上
            self::TLSv1_3     => CURL_SSLVERSION_TLSv1_3,
            // SSLv2以上
            self::SSLv2       => CURL_SSLVERSION_SSLv2,
            // SSLv3以上
            self::SSLv3       => CURL_SSLVERSION_SSLv3,
            // 最適なバージョンを上限として探す
            self::MAX_DEFAULT => CURL_SSLVERSION_MAX_DEFAULT,
//            // None
//            self::MAX_NONE    => CURL_SSLVERSION_MAX_NONE,
            // TLSv1.0以下
            self::MAX_TLSv1_0 => CURL_SSLVERSION_MAX_TLSv1_0,
            // TLSv1.1以下
            self::MAX_TLSv1_1 => CURL_SSLVERSION_MAX_TLSv1_1,
            // TLSv1.2以下
            self::MAX_TLSv1_2 => CURL_SSLVERSION_MAX_TLSv1_2,
            // TLSv1.3以下
            self::MAX_TLSv1_3 => CURL_SSLVERSION_MAX_TLSv1_3
        };
    }
}