<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Request;

use Ennacx\SimpleCurl\Enum\ContentType;
use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Exception\InvalidRequestException;
use Ennacx\SimpleCurl\Exception\RequestBodyException;
use Ennacx\SimpleCurl\Helper\Internal\Utils;
use Ennacx\SimpleCurl\Option\CurlOptions;
use Ennacx\SimpleCurl\Request\Internal\RequestAttachmentEntry;
use Ennacx\SimpleCurl\Request\Internal\RequestBody;
use JsonException;

/**
 * Immutable-style HTTP request value object.
 *
 * Static constructors are available for each CurlMethod case, for example
 * Request::get('https://example.com') and Request::post('https://example.com').
 *
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
    private readonly string $id;

    /** @var string クエリ・フラグメントを除いた送信先URL */
    private readonly string $url;

    /** @var CurlMethod HTTPメソッド */
    private readonly CurlMethod $method;

    /** @var array<string, string> 送信するHTTPヘッダー */
    private array $headers = [];

    /** @var RequestBody|null 送信するリクエストボディ */
    private ?RequestBody $body = null;

    /** @var list<RequestAttachmentEntry> 添付ファイルの配列 */
    private array $attachmentEntries = [];

    /** @var ContentType|null リクエストボディのContent-Type */
    private ?ContentType $contentType = null;

    /** @var string[] Acceptヘッダーとして送信するメディアタイプ */
    private array $acceptHeaders = [];

    /** @var array<string, mixed> 送信するクエリパラメーター */
    private array $queryParams = [];

    /** @var string|null フラグメント */
    private ?string $fragment = null;

    /**
     * Creates a request.
     *
     * Query string and fragment values are extracted from the URL and stored
     * separately so that CurlOptionsFactory can rebuild the final URL.
     *
     * @param  string     $url    Request URL.
     * @param  CurlMethod $method HTTP method.
     * @throws InvalidRequestException
     */
    public function __construct(string $url, CurlMethod $method = CurlMethod::GET){

        // ID付与
        $this->id = Utils::uuid_v4();

        // URLバリデーション
        $tempUrl = self::validateUrl($url);
        $this->method = $method;

        // GETクエリとフラグメントをRequest内部の値として分離する
        $queryString = parse_url($tempUrl, PHP_URL_QUERY);
        $fragment = parse_url($tempUrl, PHP_URL_FRAGMENT);

        if($queryString !== null){
            parse_str($queryString, $this->queryParams);
        }

        if($fragment !== null){
            $this->fragment = $fragment;
        }

        // URL本体はクエリとフラグメントを除去した状態で保持する
        $tempUrl = explode('?', $tempUrl);
        $this->url = (str_contains($tempUrl[0], '#')) ? explode('#', $tempUrl[0])[0] : $tempUrl[0];

        unset($tempUrl);
    }

    /**
     * Creates a request from a lower-case HTTP method name.
     *
     * @param  string $method Called static method name.
     * @param  array  $args   Constructor arguments. The first argument must be the URL.
     * @throws InvalidRequestException
     */
    public static function __callStatic(string $method, array $args): self {

        // HTTPメソッドの解決
        $curlMethod = self::findCurlMethod($method);
        if($curlMethod === null){
            throw new InvalidRequestException(sprintf('Invalid method: %s', $method));
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
            throw new InvalidRequestException('Request URL is required.');
        }

        return new self($url, $curlMethod);
    }

    /**
     * Returns the request ID.
     *
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * Returns the base request URL.
     *
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * Returns the HTTP method.
     *
     */
    public function getMethod(): CurlMethod {
        return $this->method;
    }

    /**
     * Returns request headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * cURLオプション生成用にリクエストボディ情報を返す。
     *
     * @internal
     */
    public function getRequestBody(): ?RequestBody {
        return $this->body;
    }

    /**
     * cURLオプション生成用に添付ファイル情報を返す。
     *
     * @return list<RequestAttachmentEntry>
     * @internal
     */
    public function getAttachmentEntries(): array {
        return $this->attachmentEntries;
    }

    /**
     * cURLオプション生成用にリクエストボディのContent-Typeを返す。
     *
     * @internal
     */
    public function getContentType(): ?ContentType {
        return $this->contentType;
    }

    /**
     * cURLオプション生成用にAcceptヘッダー値を返す。
     *
     * @return string[]
     * @internal
     */
    public function getAcceptHeaders(): array {
        return $this->acceptHeaders;
    }

    /**
     * Returns query parameters.
     *
     * @return array<string, mixed>
     */
    public function getQueryParams(): array {
        return $this->queryParams;
    }

    /**
     * Returns the URL fragment.
     *
     */
    public function getFragment(): ?string {
        return $this->fragment;
    }

    /**
     * Returns a new request with the given headers.
     *
     * @param  array<string, mixed> $headers Header names and values.
     * @throws InvalidRequestException
     */
    public function headers(array $headers): self {

        $clone = clone $this;
        $clone->headers = self::validateHeaders($headers);

        return $clone;
    }

    /**
     * Returns a new request with an Accept header value.
     *
     * Existing values are compared by media type only. If the media type already
     * exists, the current request is returned unchanged.
     *
     * @param  AcceptValueInterface|string $acceptValue Accept header value.
     * @throws InvalidRequestException
     */
    public function accept(AcceptValueInterface|string $acceptValue): self {

        $acceptValue = ($acceptValue instanceof AcceptValueInterface) ? $acceptValue->toHeaderValue() : trim($acceptValue);

        if($acceptValue === ''){
            throw new InvalidRequestException('Accept type must not be empty.');
        }

        $acceptKey = self::normalizeAcceptKey($acceptValue);

        foreach($this->acceptHeaders as $header){
            if(self::normalizeAcceptKey($header) === $acceptKey){
                return $this;
            }
        }

        $clone = clone $this;
        $clone->acceptHeaders[] = $acceptValue;

        return $clone;
    }

    /**
     * Returns a new request with multiple Accept header values.
     *
     * @param  AcceptValueInterface|string ...$acceptValues Accept header values.
     * @throws InvalidRequestException
     */
    public function accepts(AcceptValueInterface|string ...$acceptValues): self {

        $clone = clone $this;

        foreach($acceptValues as $acceptValue){
            // `accept()` 内でcloneしているため `$clone` に都度代入する
            $clone = $clone->accept($acceptValue);
        }

        return $clone;
    }

    /**
     * Returns a new request with a query parameter.
     *
     * Passing null removes the key when overwrite is true.
     *
     * @param  string  $key       Query parameter key.
     * @param  mixed   $value     Query parameter value.
     * @param  boolean $overwrite Whether an existing key should be overwritten.
     * @throws InvalidRequestException
     */
    public function param(string $key, mixed $value, bool $overwrite = true): self {

        if(!$overwrite && array_key_exists($key, $this->queryParams)){
            return $this;
        }

        $paramValue = Utils::toString($value);
        if($paramValue === false){
            throw new InvalidRequestException(sprintf('Request param "%s" has an invalid value.', $key));
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
     * Returns a new request with multiple query parameters.
     *
     * @param  array<string, mixed> $params    Query parameters.
     * @param  boolean              $overwrite Whether existing keys should be overwritten.
     * @throws InvalidRequestException
     */
    public function params(array $params, bool $overwrite = true): self {

        $params = array_filter($params, fn($k): bool => (is_string($k)), ARRAY_FILTER_USE_KEY);
        if(empty($params)){
            return $this;
        }

        $clone = clone $this;

        foreach($params as $key => $value){
            // `param()` 内でcloneしているため `$clone` に都度代入する
            $clone = $clone->param($key, $value, $overwrite);
        }

        return $clone;
    }

    /**
     * Returns a new request with a raw request body.
     *
     * @param  array|string $body        Request body.
     * @param  ContentType  $contentType Content type used to encode the body.
     * @param  array        $options     Body encoding options.
     * @throws RequestBodyException
     */
    public function body(array|string $body, ContentType $contentType = ContentType::PlainText, array $options = []): self {

        // 空ボディは即リターン
        if($body === [] || $body === ''){
            return $this;
        }

        // 既に添付ファイルが存在する場合はmultipart以外のボディを設定できない
        if($this->attachmentEntries !== [] && $contentType !== ContentType::FormUrlEncoded){
            throw new RequestBodyException('Only form fields can be combined with attachments.');
        }

        $clone = clone $this;
        $clone->body = new RequestBody($body, $contentType, $options);
        $clone->contentType = $contentType;

        return $clone;
    }

    /**
     * Returns a new request with a body loaded from a local file.
     *
     * @param  string      $path        Local file path.
     * @param  ContentType $contentType Body content type.
     * @throws RequestBodyException
     */
    public function bodyFromFile(string $path, ContentType $contentType = ContentType::PlainText): self {

        // 既に添付ファイルが存在する場合はテキストボディを設定できない
        if($this->attachmentEntries !== []){
            throw new RequestBodyException('Cannot set file body when attachments are set.');
        }

        // ファイルチェック
        Utils::fileCheck($path);

        $content = file_get_contents($path);

        if($content === false){
            throw new RequestBodyException('Failed to read target file.');
        }

        return $this->body($content, $contentType);
    }

    /**
     * Returns a new request with a JSON body.
     *
     * Arrays are encoded later by CurlOptionsFactory. Strings are validated as
     * JSON before being stored.
     *
     * @param  array<string|int, mixed>|string $input     Array to encode or JSON string.
     * @param  int                             $jsonFlags Flags passed to json_encode().
     * @param  boolean                         $throw     Whether JSON failures should throw an exception.
     * @throws RequestBodyException
     */
    public function json(array|string $input, int $jsonFlags = JSON_UNESCAPED_SLASHES, bool $throw = true): self {

        // 既に添付ファイルが存在する場合はJSONボディを設定できない
        if($this->attachmentEntries !== []){
            throw new RequestBodyException('Cannot set JSON body when attachments are set.');
        }

        if(is_string($input)){
            try{
                $inputArray = json_decode($input, true, flags: JSON_THROW_ON_ERROR);
            } catch(JsonException $e){
                if($throw){
                    throw new RequestBodyException('JSON parse error.', previous: $e);
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
     * Returns a new request with an application/x-www-form-urlencoded body.
     *
     * @param  array<string|int, mixed> $input Form fields.
     */
    public function form(array $input): self {

        $clone = clone $this;

        return $clone->body($input, contentType: ContentType::FormUrlEncoded);
    }

    /**
     * Returns a new request with a multipart/form-data file attachment.
     *
     * If attachments are present, a user-provided Content-Type header is removed
     * during option building so cURL can generate a valid multipart boundary.
     *
     * @param  RequestAttachment $attachment     File attachment.
     * @param  boolean           $allowOverwrite Whether this attachment may overwrite a field with the same name.
     * @throws RequestBodyException
     */
    public function attach(RequestAttachment $attachment, bool $allowOverwrite = true): self {

        // フィールド名が空の場合はエラー
        if(trim($attachment->name) === ''){
            throw new RequestBodyException('Attachment name must not be empty.');
        }

        // リクエストボディが設定済みの場合、フォーム形式でなければエラー
        if($this->body !== null && $this->body->contentType !== ContentType::FormUrlEncoded){
            throw new RequestBodyException("The attachment cannot be added when the request body is specified.");
        }

        // ファイルチェック
        Utils::fileCheck($attachment->path);

        // 上書き禁止時の同一名チェック
        if(!$allowOverwrite){
            $attachNames = array_map(fn(RequestAttachmentEntry $attach): string => $attach->attachment->name, $this->attachmentEntries);
            if(in_array($attachment->name, $attachNames, true)){
                throw new RequestBodyException("The attachment name is already used in attachment.");
            }

            unset($attachNames);

            $body = $this->body?->body ?? null;
            if(is_array($body) && $body !== []){
                if(array_key_exists($attachment->name, $body)){
                    throw new RequestBodyException("The attachment name is already used in body.");
                }
            }

            unset($body);
        }

        $clone = clone $this;

        if(!$allowOverwrite){
            $clone->attachmentEntries[] = new RequestAttachmentEntry(attachment: $attachment, allowOverwrite: $allowOverwrite);
        } else{
            // 単なるリストなので、同名ファイルを削除してから追加する
            $entries = array_filter($this->attachmentEntries,
                static fn(RequestAttachmentEntry $entry): bool => ($entry->attachment->name !== $attachment->name),
            );

            $clone->attachmentEntries = [
                ...array_values($entries),
                new RequestAttachmentEntry($attachment, $allowOverwrite),
            ];
        }

        // multipartではContent-Typeのboundary生成をcURLに任せるため初期化
        $clone->contentType = null;

        return $clone;
    }

    /**
     * Returns a new request with a multipart/form-data file attachment.
     *
     * @param  string  $name           Multipart field name.
     * @param  string  $path           Local file path.
     * @param  boolean $allowOverwrite Whether this attachment may overwrite a field with the same name.
     * @throws RequestBodyException
     */
    public function attachFile(string $name, string $path, bool $allowOverwrite = true): self {
        return $this->attach(new RequestAttachment($name, $path), $allowOverwrite);
    }

    /**
     * Prepares the request with optional cURL execution options.
     *
     * @param  CurlOptions|null $options Execution options.
     */
    public function prepare(?CurlOptions $options = null): PreparedRequest {
        return PreparedRequest::create($this, $options);
    }

    /**
     * URLとして利用できる最小限の形式か検証する。
     *
     * @param  string $url
     * @throws InvalidRequestException
     */
    private static function validateUrl(string $url): string {

        $url = trim($url);
        if($url === ''){
            throw new InvalidRequestException('Request URL must not be empty.');
        }

        $parts = parse_url($url);
        if($parts === false || empty($parts['scheme'])){
            throw new InvalidRequestException(sprintf('Invalid request URL: %s', $url));
        }

        return $url;
    }

    /**
     * 送信ヘッダーを検証し、文字列値の連想配列へ正規化する。
     *
     * @param  array<string, mixed> $headers
     * @return array<string, string>
     * @throws InvalidRequestException
     */
    private static function validateHeaders(array $headers): array {

        $ret = [];
        foreach($headers as $name => $value){
            if(!is_string($name) || trim($name) === ''){
                throw new InvalidRequestException('Request header name must be a non-empty string.');
            }

            $headerValue = Utils::toString($value);
            if($headerValue === false){
                throw new InvalidRequestException(sprintf('Request header "%s" has an invalid value.', $name));
            }

            $headerName = trim($name);
            if($headerValue === ''){
                throw new InvalidRequestException(sprintf('Request header "%s" must not be empty.', $headerName));
            }

            $ret[$headerName] = $headerValue;
        }

        return $ret;
    }

    /**
     * メソッド名からCurlMethodを取得する。
     *
     * @param  string $method
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

    /**
     * Acceptヘッダー値から重複判定用のメディアタイプを取り出す。
     *
     * @param  string $acceptValue
     */
    private static function normalizeAcceptKey(string $acceptValue): string {
        return strtolower(trim(explode(';', $acceptValue, 2)[0]));
    }
}
