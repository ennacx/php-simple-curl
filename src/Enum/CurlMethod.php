<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * HTTP メソッド
 */
enum CurlMethod {

    /** HTTP GETモード */
    case GET;

    /** HTTP POSTモード */
    case POST;

    /** HTTP PUTモード */
    case PUT;

    /** HTTP DELETEモード */
    case DELETE;

    /** HTTP PATCHモード */
    case PATCH;

    /** HTTP HEADモード */
    case HEAD;

    /** HTTP OPTIONSモード */
    case OPTIONS;

    /**
     * HTTPメソッドに対応するcURLオプションを返す。
     *
     * @return array<int, mixed>
     */
    public function toCurlOptions(): array {

        return match($this){
            self::GET => [
                CURLOPT_HTTPGET => true,
            ],
            self::POST => [
                CURLOPT_POST => true,
            ],
            self::PUT => [
                CURLOPT_CUSTOMREQUEST => 'PUT',
            ],
            self::DELETE => [
                CURLOPT_CUSTOMREQUEST => 'DELETE',
            ],
            self::PATCH => [
                CURLOPT_CUSTOMREQUEST => 'PATCH',
            ],
            self::HEAD => [
                CURLOPT_NOBODY => true,
            ],
            self::OPTIONS => [
                CURLOPT_CUSTOMREQUEST => 'OPTIONS',
            ],
        };
    }
}
