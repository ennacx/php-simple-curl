<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

/**
 * HTTP methods supported by Request.
 */
enum CurlMethod {

    /** HTTP GET. */
    case GET;

    /** HTTP POST. */
    case POST;

    /** HTTP PUT. */
    case PUT;

    /** HTTP DELETE. */
    case DELETE;

    /** HTTP PATCH. */
    case PATCH;

    /** HTTP HEAD. */
    case HEAD;

    /** HTTP OPTIONS. */
    case OPTIONS;

    /**
     * Converts the method into cURL options.
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
