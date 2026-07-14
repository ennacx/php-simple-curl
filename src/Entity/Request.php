<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Enum\ContentType;
use Ennacx\SimpleCurl\Static\Utils;
use InvalidArgumentException;
use JsonException;

/**
 * cURLで送信するリクエスト内容を表す値オブジェクト。
 *
 * `CurlMethod` の列挙子を小文字にした静的Factoryを提供する。
 * @method static self get(string $url)
 * @method static self post(string $url)
 * @method static self put(string $url)
 * @method static self delete(string $url)
 * @method static self patch(string $url)
 * @method static self head(string $url)
 * @method static self options(string $url)
 */
final class Request {

    /** @var string Requestを識別するID */
    public readonly string $id;

    /** @var array<string, mixed> 送信するHTTPヘッダー */
    public array $requestHeaders = [];

    /** @var RequestBody|null 送信するリクエストボディー */
    public ?RequestBody $requestBody = null;

    /** @var list<RequestAttachment> 添付ファイルの配列 */
    public array $attachments = [];

    /** @var ContentType|null リクエストボディーのContent-Type */
    public ?ContentType $contentType = null;

    /** @var string[] Acceptヘッダーとして送信するメディアタイプ */
    public array $acceptHeaders = [];

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

        $this->id  = Utils::uuid_v4();
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
     * Acceptヘッダーへ送信可能なメディアタイプを追加する。
     *
     * `ContentType` enumだけでなく、`application/vnd.api+json` のような任意のメディアタイプ文字列も指定できる。
     * 同じ値が既に追加されている場合は、現在のRequestをそのまま返す。
     *
     * @param  ContentType|string $contentType Acceptヘッダーに追加するメディアタイプ
     * @return self
     */
    public function accept(ContentType|string $contentType): self {

        $contentType = ($contentType instanceof ContentType) ? $contentType->value : trim($contentType);

        if($contentType === ''){
            throw new InvalidArgumentException('Accept type must not be empty.');
        }

        if(in_array($contentType, $this->acceptHeaders, true)){
            return $this;
        }

        $clone = clone $this;

        $clone->acceptHeaders[] = $contentType;

        return $clone;
    }

    /**
     * 複数のメディアタイプをAcceptヘッダーへ追加する。
     *
     * @param  ContentType|string ...$contentTypes Acceptヘッダーに追加するメディアタイプ
     * @return self
     */
    public function accepts(ContentType|string ...$contentTypes): self {

        $clone = clone $this;

        foreach($contentTypes as $contentType){
            // `accept()` 内でcloneしてしまっているため `$clone` に都度代入
            $clone = $clone->accept($contentType);
        }

        return $clone;
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
            // `param()` 内でcloneしてしまっているため `$clone` に都度代入
            $clone = $clone->param($key, $value, $overwrite);
        }

        return $clone;
    }

    /**
     * 送信するリクエストボディーを設定する。
     *
     * 引数で受け取った文字列をそのまま保持し、`CurlOptionsFactory`で `CURLOPT_POSTFIELDS` と、
     * 既定の `Content-Type` へ変換する。
     *
     * @param  array|string $body        送信するリクエストボディー
     * @param  ContentType  $contentType ボディー形式に対応するContent-Type
     * @param  array        $options     リクエストボディーのオプション
     * @return self
     */
    public function body(array|string $body, ContentType $contentType = ContentType::PlainText, array $options = []): self {

        if($body === ''){
            return $this;
        }

        $clone = clone $this;

        $clone->requestBody = new RequestBody($body, $contentType, $options);
        $clone->contentType = $contentType;

        return $clone;
    }

    /**
     * ファイルの内容をボディーに設定する。
     *
     * @param  string      $path        ファイルパス
     * @param  ContentType $contentType Content-Type (Enum)
     * @return self
     * @throws InvalidArgumentException ファイルが存在しない、または読取不可の場合
     */
    public function bodyFromFile(string $path, ContentType $contentType = ContentType::PlainText): self {

        // ファイルチェックを兼ねた取得
        $content = Utils::getFileContents($path);

        return $this->body($content, $contentType);
    }

    /**
     * 配列またはJSON文字列をJSONリクエストボディーとして設定する。
     *
     * 既定では `JSON_THROW_ON_ERROR` を有効にし、Content-Typeには `application/json` を使用する。
     *
     * @param  array<string|int, mixed>|string $input     JSON化する配列、または検証済みとして送信するJSON文字列
     * @param  int                             $jsonFlags 配列をJSON化する場合に `json_encode()` へ渡すJSONフラグ
     * @param  boolean                         $throw     JSON変換失敗時に例外を投げる場合はtrue
     * @return self
     * @throws JsonException `$throw = true` の時、JSON変換失敗時に投げられる例外
     */
    public function json(array|string $input, int $jsonFlags = JSON_UNESCAPED_SLASHES, bool $throw = true): self {

        if(is_string($input)){
            try{
                $inputArray = json_decode($input, true, flags: JSON_THROW_ON_ERROR);
            } catch(JsonException $e){
                if($throw){
                    throw $e;
                }

                return $this;
            }
        } else{
            if($throw){
                $jsonFlags |= JSON_THROW_ON_ERROR;
            }

            $inputArray = $input;
        }

        $clone = clone $this;

        return $clone->body($inputArray, contentType: ContentType::Json, options: ['flags' => $jsonFlags]);
    }

    /**
     * 配列を `application/x-www-form-urlencoded` 形式のリクエストボディーとして設定する。
     *
     * @param  array<string|int, mixed> $input     フォーム送信用の値
     * @param  boolean                  $overwrite ファイル添付時、同名のフィールドが存在する場合に上書きするかどうか
     * @return self
     */
    public function form(array $input, bool $overwrite = true): self {

        $clone = clone $this;

        return $clone->body($input, contentType: ContentType::FormUrlEncoded, options: ['overwrite' => $overwrite]);
    }

    /**
     * multipart/form-dataで送信する添付ファイルを追加する。
     *
     * 添付ファイルは、リクエストボディ未指定または `form()` のフォーム項目と組み合わせて送信できる。
     *
     * 添付ファイルがある場合、送信時のContent-TypeはcURLがboundary付きで生成するため、
     * Factory側でユーザー指定のContent-Typeヘッダーを削除する。
     *
     * @param  RequestAttachment $attachment 添付ファイル情報
     * @return self
     * @throws InvalidArgumentException 添付ファイルが存在しない、または読取不可の場合
     */
    public function attach(RequestAttachment $attachment): self {

        // ファイルチェック
        Utils::getFileContents($attachment->path);

        $clone = clone $this;

        // 添付ファイル配列に追加
        $clone->attachments[] = $attachment;

        // (不要になるが) 念のためmultipartで設定
        $clone->contentType = ContentType::MultipartFormData;

        return $clone;
    }

    /**
     * 任意にCurlOptionsを付与し送信待ちリクエストを生成する。
     *
     * @param  CurlOptions|null $options cURL実行時のオプション設定
     * @return PreparedRequest
     */
    public function prepare(?CurlOptions $options = null): PreparedRequest {
        return PreparedRequest::create($this, $options);
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
