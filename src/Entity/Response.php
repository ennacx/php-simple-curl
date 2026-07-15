<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Enum\CurlError;
use Ennacx\SimpleCurl\Exception\InvalidResponseException;
use JsonException;

/**
 * cURL実行後のレスポンス情報を保持する値オブジェクト。
 */
final readonly class Response {

    /** @var string[] cURLから取得した生のレスポンスヘッダー行 */
    private array $rawHeaders;

    /** @var array<string, string|string[]> 小文字化したヘッダー名をキーにしたレスポンスヘッダー */
    private array $parsedHeaders;

    /**
     * コンストラクタ
     *
     * @param int            $statusCode   HTTPステータスコード
     * @param string[]       $headers      レスポンスヘッダー行
     * @param string|null    $body         レスポンスボディ
     * @param array          $info         curl_getinfo()の結果
     * @param CurlError|null $error        cURLエラー。成功時はnull
     * @param string         $errorMessage cURLエラーメッセージ
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
     * 1xx系の情報レスポンスか判定する。
     *
     * @return boolean
     */
    public function isInformational(): bool {
        return ($this->statusCode >= 100 && $this->statusCode < 200);
    }

    /**
     * HTTP 200 OK レスポンスか判定する。
     *
     * @return boolean
     */
    public function isOk(): bool {
        return ($this->error === null && $this->statusCode === 200);
    }

    /**
     * cURLエラーがなく、2xx系の成功レスポンスか判定する。
     *
     * @return boolean
     */
    public function isSuccessful(): bool {
        return ($this->error === null && $this->statusCode >= 200 && $this->statusCode < 300);
    }

    /**
     * 3xx系のリダイレクトレスポンスか判定する。
     *
     * @return boolean
     */
    public function isRedirect(): bool {
        return ($this->statusCode >= 300 && $this->statusCode < 400);
    }

    /**
     * 4xx系のクライアントエラーレスポンスか判定する。
     *
     * @return boolean
     */
    public function isClientError(): bool {
        return ($this->statusCode >= 400 && $this->statusCode < 500);
    }

    /**
     * 5xx系のサーバーエラーレスポンスか判定する。
     *
     * @return boolean
     */
    public function isServerError(): bool {
        return ($this->statusCode >= 500 && $this->statusCode < 600);
    }

    /**
     * cURLエラー、または4xx/5xxレスポンスか判定する。
     *
     * @return boolean
     */
    public function isError(): bool {
        return ($this->error !== null || $this->isClientError() || $this->isServerError());
    }

    /**
     * 指定したレスポンスヘッダーが存在するか判定する。
     *
     * @param  string  $key ヘッダー名
     * @return boolean
     */
    public function hasHeader(string $key): bool {
        return array_key_exists(strtolower($key), $this->parsedHeaders);
    }

    /**
     * 指定したレスポンスヘッダーを取得する。
     * ※同名ヘッダーが複数ある場合は文字列配列を返す。
     *
     * @param  string $key ヘッダー名
     * @return string|string[]|null
     */
    public function header(string $key): string|array|null {
        return $this->parsedHeaders[strtolower($key)] ?? null;
    }

    public function rawHeaders(): array {
        return $this->rawHeaders;
    }

    /**
     * パース済みのレスポンスヘッダーを返す。
     *
     * @return array<string, string|string[]>
     */
    public function headers(): array {
        return $this->parsedHeaders;
    }

    /**
     * レスポンスボディをJSONとしてデコードする。
     *
     * @param  boolean $associative `true`の場合は連想配列として返す
     * @param  boolean $throw       `true`の場合はJSONデコード失敗時に`InvalidResponseException`をスローする
     * @return mixed
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
            }
            // キーが重複した場合は配列に変換して値を追加
            else if(!is_array($parsedHeaders[$key])){
                $parsedHeaders[$key] = [$parsedHeaders[$key], $value];
            }
            // キーが重複し、且つ既に配列化されている場合は追加
            else{
                $parsedHeaders[$key][] = $value;
            }
        }

        return $parsedHeaders;
    }
}
