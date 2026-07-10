<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Enum\RequestContentType;
use Ennacx\SimpleCurl\Static\Utils;
use InvalidArgumentException;

/**
 * cURLで送信するリクエスト内容を表す値オブジェクト。
 *
 * `CurlMethod` の列挙子を小文字にした静的Factoryを提供する。
 * @method static get(string $url)
 * @method static post(string $url)
 * @method static put(string $url)
 * @method static delete(string $url)
 * @method static patch(string $url)
 * @method static head(string $url)
 * @method static options(string $url)
 */
final class Request {

    /** @var string Requestを識別するID */
    public readonly string $id;

    /** @var array<string, mixed> 送信するHTTPヘッダー */
    public array $requestHeaders = [];

    /** @var string|null 送信するリクエストボディー */
    public ?string $requestBody = null;

    /** @var RequestContentType|null リクエストボディーのContent-Type */
    public ?RequestContentType $requestContentType = null;

    /** @var array<string, mixed> 送信するクエリパラメータ */
    public array $queryParams = [];

    /** @var string|null フラグメント */
    public ?string $fragment = null;

    /**
     * コンストラクタ
     *
     * @param string     $url    送信先URL
     * @param CurlMethod $method HTTPメソッド
     */
    public function __construct(public string $url, public CurlMethod $method = CurlMethod::GET){

        $this->id = Utils::uuid_v4();

        $this->url = self::validateUrl($this->url);

        // GETクエリ取得
        $queryString = parse_url($this->url, PHP_URL_QUERY);
        // フラグメント取得
        $fragment = parse_url($this->url, PHP_URL_FRAGMENT);

        // GETクエリが存在する場合
        if($queryString !== null){
            // 配列に格納
            parse_str($queryString, $this->queryParams);
        }

        // フラグメントが存在する場合
        if($fragment !== null){
            $this->fragment = $fragment;
        }

        // URLからクエリとフラグメントを除去
        $tempUrl = explode('?', $url);
        $this->url = (str_contains($tempUrl[0], '#')) ? explode('#', $tempUrl[0])[0] : $tempUrl[0];

        unset($tempUrl);
    }

    /**
     * Request::get('https://example.com') のような、HTTPメソッド名の静的Factoryを提供する。
     *
     * @param  string $method 呼び出された静的メソッド名
     * @param  array  $args   Requestコンストラクタへ渡す引数 (現在`url`のみ)
     * @return self
     */
    public static function __callStatic(string $method, array $args): self {

        // HTTPメソッドの解決
        $curlMethod = self::findCurlMethod($method);
        if($curlMethod === null){
            throw new InvalidArgumentException(sprintf('Invalid method: %s', $method));
        }

        // URL取得
        $url = null;
        if(isset($args['url']) && is_string($args['url'])){
            $url = $args['url'];
            unset($args['url']);
        } else if(isset($args[0]) && is_string($args[0])){
            $url = $args[0];
            unset($args[0]);
        }

        if($url === null){
            throw new InvalidArgumentException('Request URL is required.');
        }

        return new self($url, $curlMethod);
    }

    /**
     * 送信するHTTPヘッダーを設定する。
     *
     * @param  array<string, mixed> $headers ヘッダー名をキーにした連想配列
     * @return self
     */
    public function headers(array $headers): self {

        $this->requestHeaders = self::validateHeaders($headers);

        return $this;
    }

    /**
     * 単一のGETパラメーターを登録する。
     *
     * @param  string  $key       クエリパラメーターのキー名
     * @param  mixed   $value     設定値 (`null`時は指定キーをクエリパラメーターから除外する)
     * @param  boolean $overwrite 既存項目を上書きする場合は、$overwriteをtrueに設定 [Default: `true`]
     * @return self
     */
    public function param(string $key, mixed $value, bool $overwrite = true): self {

        if(!$overwrite && array_key_exists($key, $this->queryParams)){
            return $this;
        }

        $paramValue = Utils::toString($value);
        if($paramValue === false){
            throw new InvalidArgumentException(sprintf('Request param "%s" has an invalid value.', $key));
        }

        $clone = clone $this;

        if($overwrite){
            if(array_key_exists($key, $clone->queryParams) && $paramValue === null){
                unset($clone->queryParams[$key]);
            } else{
                $clone->queryParams[$key] = $paramValue;
            }

            return $clone;
        } else if(!array_key_exists($key, $clone->queryParams) && $paramValue !== null){
            $clone->queryParams[$key] = $paramValue;

            return $clone;
        }

        return $this;
    }

    /**
     * 複数のGETパラメーターを一括して登録する。
     *
     * @param  array<string, mixed> $params    `$overwrite = true` 且つ `value = null` の場合は対象キーをクエリパラメーターから除外する
     * @param  boolean              $overwrite 既存項目を上書きする場合は、$overwriteをtrueに設定 [Default: `true`]
     * @return self
     */
    public function params(array $params, bool $overwrite = true): self {

        $params = array_filter($params, fn($k): bool => (is_string($k)), ARRAY_FILTER_USE_KEY);
        if(empty($params)){
            return $this;
        }

        $clone = clone $this;

        foreach($params as $key => $value){
            $clone = $clone->param($key, $value, $overwrite);
        }

        return $clone;
    }

    /**
     * 送信するリクエストボディーを設定する。
     *
     * 引数で受け取った文字列をそのまま保持し、`CurlOptionsFactory`で
     * `CURLOPT_POSTFIELDS` と既定の `Content-Type` へ変換する。
     *
     * @param  string             $body        送信するリクエストボディー
     * @param  RequestContentType $contentType ボディー形式に対応するContent-Type
     * @return self
     */
    public function body(string $body, RequestContentType $contentType = RequestContentType::PlainText): self {

        if($body === ''){
            return $this;
        }

        $clone = clone $this;

        $clone->requestBody        = $body;
        $clone->requestContentType = $contentType;

        return $clone;
    }

    /**
     * ファイルの内容をボディーに設定する。
     *
     * @param  string             $path
     * @param  RequestContentType $contentType
     * @return self
     */
    public function bodyFromFile(string $path, RequestContentType $contentType = RequestContentType::PlainText): self {

        if(!file_exists($path) || !is_readable($path)){
            throw new InvalidArgumentException('Target file does not exist or is not readable.');
        } else if(!is_file($path)){
            throw new InvalidArgumentException('Target path is not a file.');
        }

        $content = file_get_contents($path);

        if($content === false){
            throw new InvalidArgumentException('Failed to read target file.');
        }

        return $this->body($content, $contentType);
    }

    /**
     * 配列をJSON文字列へ変換し、JSONリクエストボディーとして設定する。
     *
     * 既定では `JSON_THROW_ON_ERROR` を有効にし、Content-Typeには `application/json` を使用する。
     *
     * @param  array<string|int, mixed> $input     JSON化する値
     * @param  int                      $jsonFlags `json_encode()` へ渡すJSONフラグ
     * @param  boolean                  $throw     JSON変換失敗時に例外を投げる場合はtrue
     * @return self
     */
    public function json(array $input, int $jsonFlags = JSON_UNESCAPED_SLASHES, bool $throw = true): self {

        if($throw){
            $jsonFlags |= JSON_THROW_ON_ERROR;
        }

        $json = json_encode($input, $jsonFlags);

        if($json === false){
            return $this;
        }

        $clone = clone $this;

        return $clone->body($json, contentType: RequestContentType::Json);
    }

    /**
     * 配列を `application/x-www-form-urlencoded` 形式のリクエストボディーとして設定する。
     *
     * @param  array<string|int, mixed> $input フォーム送信用の値
     * @return self
     */
    public function form(array $input): self {

        $clone = clone $this;

        return $clone->body(http_build_query($input), RequestContentType::FormUrlEncoded);
    }

    /**
     * URLとして利用できる最低限の形式か検証する。
     *
     * @param  string $url
     * @return string
     */
    private static function validateUrl(string $url): string {

        $url = trim($url);
        if($url === ''){
            throw new InvalidArgumentException('Request URL must not be empty.');
        }

        $parts = parse_url($url);
        if($parts === false || empty($parts['scheme'])){
            throw new InvalidArgumentException(sprintf('Invalid request URL: %s', $url));
        }

        return $url;
    }

    /**
     * CurlOptionsを指定せず、デフォルト設定で送信待ちリクエストを生成する。
     *
     * @return ConfiguredRequest
     */
    public function asConfigured(): ConfiguredRequest {

        return ConfiguredRequest::create($this, null);
    }

    /**
     * CurlOptionsを付与した送信待ちリクエストを生成する。
     *
     * @param  CurlOptions $options cURL実行時のオプション設定
     * @return ConfiguredRequest
     */
    public function withOptions(CurlOptions $options): ConfiguredRequest {

        return ConfiguredRequest::create($this, $options);
    }

    /**
     * 送信ヘッダーを検証し、文字列値の連想配列へ正規化する。
     *
     * @param  array<string, mixed> $headers
     * @return array<string, string>
     */
    private static function validateHeaders(array $headers): array {

        $ret = [];
        foreach($headers as $name => $value){
            if(!is_string($name) || trim($name) === ''){
                throw new InvalidArgumentException('Request header name must be a non-empty string.');
            }

            $headerValue = Utils::toString($value);
            if($headerValue === false){
                throw new InvalidArgumentException(sprintf('Request header "%s" has an invalid value.', $name));
            }

            $headerName = trim($name);
            if($headerValue === ''){
                throw new InvalidArgumentException(sprintf('Request header "%s" must not be empty.', $headerName));
            }

            $ret[$headerName] = $headerValue;
        }

        return $ret;
    }

    /**
     * メソッド名からCurlMethodを取得する。
     *
     * @param  string $method
     * @return CurlMethod|null
     */
    private static function findCurlMethod(string $method): ?CurlMethod {

        $method = strtolower($method);
        foreach(CurlMethod::cases() as $curlMethod){
            if(strtolower($curlMethod->name) === $method){
                return $curlMethod;
            }
        }

        return null;
    }
}
