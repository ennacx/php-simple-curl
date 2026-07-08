<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Static\Utils;
use InvalidArgumentException;
use Stringable;

/**
 * cURLで送信するリクエスト内容を表す値オブジェクト。
 */
final class Request {

    /** @var string Requestを識別するID */
    public readonly string $id;

    /** @var array<string, mixed> 送信するHTTPヘッダー */
    public array $requestHeaders = [];

    /**
     * コンストラクタ
     *
     * @param string     $url    送信先URL
     * @param CurlMethod $method HTTPメソッド
     */
    public function __construct(public string $url, public CurlMethod $method = CurlMethod::GET){

        $this->id  = Utils::uuid_v4();
        $this->url = self::validateUrl($this->url);
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
     * CurlOptionsを指定せず、デフォルト設定で送信待ちリクエストを生成する。
     *
     * @return PendingRequest
     */
    public function asPending(): PendingRequest {

        return PendingRequest::create($this, null);
    }

    /**
     * CurlOptionsを付与した送信待ちリクエストを生成する。
     *
     * @param  CurlOptions $options cURL実行時のオプション設定
     * @return PendingRequest
     */
    public function withOptions(CurlOptions $options): PendingRequest {

        return PendingRequest::create($this, $options);
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

            if(is_string($value) || is_numeric($value)){
                $headerValue = trim((string)$value);
            } else if($value instanceof Stringable){
                $headerValue = trim($value->__toString());
            } else{
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
