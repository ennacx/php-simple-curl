<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Enum\ContentType;
use Ennacx\SimpleCurl\Exception\InvalidRequestException;
use Ennacx\SimpleCurl\Exception\RequestBodyException;
use Ennacx\SimpleCurl\Static\Utils;
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

    /** @var list<RequestAttachmentEntry> 添付ファイルの配列 */
    public array $attachmentEntries = [];

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
     * `ContentType` enumや`MediaRange` enumだけでなく、
     * `application/vnd.api+json` のような任意のメディアタイプ文字列も指定できる。
     * 同じ値が既に追加されている場合は、現在のRequestをそのまま返す。
     *
     * @param  AcceptValue|string $acceptValue Acceptヘッダーに追加するメディアタイプ
     * @return self
     * @throws InvalidRequestException
     */
    public function accept(AcceptValue|string $acceptValue): self {

        $acceptValue = ($acceptValue instanceof AcceptValue) ? $acceptValue->toHeaderValue() : trim($acceptValue);

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
     * 複数のメディアタイプをAcceptヘッダーへ追加する。
     *
     * @param  AcceptValue|string ...$acceptValues Acceptヘッダーに追加するメディアタイプ
     * @return self
     * @throws InvalidRequestException
     */
    public function accepts(AcceptValue|string ...$acceptValues): self {

        $clone = clone $this;

        foreach($acceptValues as $acceptValue){
            // `accept()` 内でcloneしてしまっているため `$clone` に都度代入
            $clone = $clone->accept($acceptValue);
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
     * 複数のGETパラメーターを一括して登録する。
     *
     * @param  array<string, mixed> $params    `$overwrite = true` 且つ `value = null` の場合は対象キーをクエリパラメーターから除外する
     * @param  boolean              $overwrite 既存項目を上書きする場合は、$overwriteをtrueに設定 [Default: `true`]
     * @return self
     * @throws InvalidRequestException
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
     * @throws RequestBodyException
     */
    public function body(array|string $body, ContentType $contentType = ContentType::PlainText, array $options = []): self {

        // 空ボディーは即リターン
        if($body === [] || $body === ''){
            return $this;
        }

        // 既に添付ファイルが存在する場合はmultipart以外のボディーを設定できない
        if($this->attachmentEntries !== [] && $contentType !== ContentType::FormUrlEncoded){
            throw new RequestBodyException('Only form fields can be combined with attachments.');
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
     * @throws RequestBodyException     ファイルが存在しない、または読取不可の場合
     */
    public function bodyFromFile(string $path, ContentType $contentType = ContentType::PlainText): self {

        // 既に添付ファイルが存在する場合はテキストボディーを設定できないためエラー
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
     * 配列またはJSON文字列をJSONリクエストボディーとして設定する。
     *
     * 既定では `JSON_THROW_ON_ERROR` を有効にし、Content-Typeには `application/json` を使用する。
     *
     * @param  array<string|int, mixed>|string $input     JSON化する配列、または検証済みとして送信するJSON文字列
     * @param  int                             $jsonFlags 配列をJSON化する場合に `json_encode()` へ渡すJSONフラグ
     * @param  boolean                         $throw     JSON変換失敗時に例外を投げる場合はtrue
     * @return self
     * @throws RequestBodyException `$throw = true` の時、JSON変換失敗時に投げられる例外
     */
    public function json(array|string $input, int $jsonFlags = JSON_UNESCAPED_SLASHES, bool $throw = true): self {

        // 既に添付ファイルが存在する場合はJSONボディーを設定できないためエラー
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
     * 配列を `application/x-www-form-urlencoded` 形式のリクエストボディーとして設定する。
     *
     * @param  array<string|int, mixed> $input フォーム送信用の値
     * @return self
     */
    public function form(array $input): self {

        $clone = clone $this;

        return $clone->body($input, contentType: ContentType::FormUrlEncoded);
    }

    /**
     * multipart/form-dataで送信する添付ファイルを追加する。
     *
     * 添付ファイルは、リクエストボディ未指定または `form()` のフォーム項目と組み合わせて送信できる。
     *
     * 添付ファイルがある場合、送信時のContent-TypeはcURLがboundary付きで生成するため、
     * Factory側でユーザー指定のContent-Typeヘッダーを削除する。
     *
     * @param  RequestAttachment $attachment     添付ファイル情報
     * @param  boolean           $allowOverwrite ファイル添付時、同名のフィールドが存在する場合に上書きを許可するかどうか
     * @return self
     * @throws RequestBodyException 添付ファイルが存在しない、または読取不可の場合
     */
    public function attach(RequestAttachment $attachment, bool $allowOverwrite = true): self {

        // フィールド名が空の場合はエラー
        if(trim($attachment->name) === ''){
            throw new RequestBodyException('Attachment name must not be empty.');
        }

        // リクエストボディーが設定済みの場合、フォーム形式で無いとエラー
        if($this->requestBody !== null && $this->requestBody->contentType !== ContentType::FormUrlEncoded){
            throw new RequestBodyException("The attachment cannot be added when the request body is specified.");
        }

        // ファイルチェック
        Utils::fileCheck($attachment->path);

        // 上書禁止時の同一名チェック
        if(!$allowOverwrite){
            // 添付ファイル側の方
            $attachNames = array_map(fn(RequestAttachmentEntry $attach): string => $attach->attachment->name, $this->attachmentEntries);
            if(in_array($attachment->name, $attachNames, true)){
                throw new RequestBodyException("The attachment name is already used in attachment.");
            }

            unset($attachNames);

            // リクエストボディーの方
            $body = $this->requestBody?->body ?? null;
            if(is_array($body) && $body !== []){
                if(array_key_exists($attachment->name, $body)){
                    throw new RequestBodyException("The attachment name is already used in body.");
                }
            }

            unset($body);
        }

        $clone = clone $this;

        // 添付ファイル配列に追加
        if(!$allowOverwrite){
            $clone->attachmentEntries[] = new RequestAttachmentEntry(attachment: $attachment, allowOverwrite: $allowOverwrite);
        } else{
            // 単なるリストなのでまず同名ファイルを削除してから追加
            $entries = array_filter($this->attachmentEntries,
                static fn(RequestAttachmentEntry $entry): bool => ($entry->attachment->name !== $attachment->name),
            );

            $clone->attachmentEntries = [
                ...array_values($entries),
                new RequestAttachmentEntry($attachment, $allowOverwrite),
            ];
        }

        // multipartでのContent-Type指定は強制させないため初期化
        $clone->contentType = null;

        return $clone;
    }

    /**
     * multipart/form-dataで送信する添付ファイルを追加する。
     *
     *  添付ファイルは、リクエストボディ未指定または `form()` のフォーム項目と組み合わせて送信できる。
     *
     *  添付ファイルがある場合、送信時のContent-TypeはcURLがboundary付きで生成するため、
     *  Factory側でユーザー指定のContent-Typeヘッダーを削除する。
     *
     * @param  string  $name           multipartフィールド名
     * @param  string  $path           添付するローカルファイルパス
     * @param  boolean $allowOverwrite ファイル添付時、同名のフィールドが存在する場合に上書きを許可するかどうか
     * @return self
     * @throws RequestBodyException
     */
    public function attachFile(string $name, string $path, bool $allowOverwrite = true): self {
        return $this->attach(new RequestAttachment($name, $path), $allowOverwrite);
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

    /**
     * Acceptヘッダー値から重複判定用のメディアタイプを取り出す。
     *
     * @param  string $acceptValue
     * @return string
     */
    private static function normalizeAcceptKey(string $acceptValue): string {
        return strtolower(trim(explode(';', $acceptValue, 2)[0]));
    }
}
