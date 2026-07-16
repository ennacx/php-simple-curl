<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Response;

use Ennacx\SimpleCurl\Enum\CurlError;
use Ennacx\SimpleCurl\Exception\InvalidResponseException;
use JsonException;

/**
 * HTTP response returned after cURL execution.
 */
final readonly class Response {

    /** @var string[] cURLから取得した生のレスポンスヘッダー行 */
    private array $rawHeaders;

    /** @var array<string, string|string[]> 小文字化したヘッダー名をキーにしたレスポンスヘッダー */
    private array $parsedHeaders;

    /**
     * Creates a response object.
     *
     * @param int            $statusCode   HTTP status code.
     * @param string[]       $headers      Raw response header lines.
     * @param string|null    $body         Response body.
     * @param array          $info         curl_getinfo() result.
     * @param CurlError|null $error        cURL error. Null on successful transfer.
     * @param string         $errorMessage cURL error message.
     */
    public function __construct(
        public int        $statusCode,
        private array     $headers,
        public ?string    $body,
        public array      $info,
        public ?CurlError $error        = null,
        public string     $errorMessage = '',
    ){
        $this->rawHeaders    = $headers;
        $this->parsedHeaders = self::parseHeaders($headers);
    }

    /**
     * Checks whether the response status is 1xx.
     */
    public function isInformational(): bool {
        return ($this->statusCode >= 100 && $this->statusCode < 200);
    }

    /**
     * Checks whether the response is HTTP 200 OK and has no cURL error.
     */
    public function isOk(): bool {
        return ($this->error === null && $this->statusCode === 200);
    }

    /**
     * Checks whether the response is 2xx and has no cURL error.
     */
    public function isSuccessful(): bool {
        return ($this->error === null && $this->statusCode >= 200 && $this->statusCode < 300);
    }

    /**
     * Checks whether the response status is 3xx.
     */
    public function isRedirect(): bool {
        return ($this->statusCode >= 300 && $this->statusCode < 400);
    }

    /**
     * Checks whether the response status is 4xx.
     */
    public function isClientError(): bool {
        return ($this->statusCode >= 400 && $this->statusCode < 500);
    }

    /**
     * Checks whether the response status is 5xx.
     */
    public function isServerError(): bool {
        return ($this->statusCode >= 500 && $this->statusCode < 600);
    }

    /**
     * Checks whether the response has a cURL error or a 4xx/5xx status.
     */
    public function isError(): bool {
        return ($this->error !== null || $this->isClientError() || $this->isServerError());
    }

    /**
     * Checks whether a response header exists.
     *
     * Header names are case-insensitive.
     *
     * @param string $key Header name.
     */
    public function hasHeader(string $key): bool {
        return array_key_exists(strtolower($key), $this->parsedHeaders);
    }

    /**
     * Returns a parsed response header value.
     *
     * If the same header appears multiple times, an array of values is returned.
     *
     * @param  string $key Header name.
     * @return string|string[]|null
     */
    public function header(string $key): string|array|null {
        return $this->parsedHeaders[strtolower($key)] ?? null;
    }

    /**
     * Returns raw response header lines.
     *
     * @return string[]
     */
    public function rawHeaders(): array {
        return $this->rawHeaders;
    }

    /**
     * Returns parsed response headers.
     *
     * @return array<string, string|string[]>
     */
    public function headers(): array {
        return $this->parsedHeaders;
    }

    /**
     * Decodes the response body as JSON.
     *
     * @param  boolean $associative Whether objects should be decoded as associative arrays.
     * @param  boolean $throw       Whether JSON decode failures should throw InvalidResponseException.
     * @throws InvalidResponseException
     */
    public function json(bool $associative = true, bool $throw = true): mixed {

        if($this->body === null || $this->body === ''){
            if($throw){
                throw new InvalidResponseException('Response body is empty.');
            }

            return null;
        }

        try{
            return json_decode(
                json:        $this->body,
                associative: $associative,
                flags:       ($throw) ? JSON_THROW_ON_ERROR : 0
            );
        } catch(JsonException $e){
            throw new InvalidResponseException('Failed to decode JSON.', previous: $e);
        }
    }

    /**
     * 生のレスポンスヘッダー行を、ヘッダー名で参照できる配列へ変換する。
     *
     * @param  string[] $rawHeaders
     * @return array<string, string|string[]>
     */
    private static function parseHeaders(array $rawHeaders): array {

        $parsedHeaders = [];
        foreach($rawHeaders as $rawHeader){
            // `HTTP/1.1 200 OK` のような ':' が無い行は無視する
            $parts = explode(':', $rawHeader, 2);
            if(!isset($parts[1])){
                continue;
            }

            // キーは小文字化して参照しやすいように
            $key   = strtolower(trim($parts[0]));
            $value = trim($parts[1]);

            // キーが存在しない場合はまず値を格納する
            if(!array_key_exists($key, $parsedHeaders)){
                $parsedHeaders[$key] = $value;
            // キーが重複した場合は配列に変換して値を追加
            } else if(!is_array($parsedHeaders[$key])){
                $parsedHeaders[$key] = [$parsedHeaders[$key], $value];
            // キーが重複し、且つ既に配列化されている場合は追加
            } else{
                $parsedHeaders[$key][] = $value;
            }
        }

        return $parsedHeaders;
    }
}
