<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Entity\Config\AuthConfig;
use Ennacx\SimpleCurl\Entity\Config\ProxyConfig;
use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;
use Ennacx\SimpleCurl\Entity\Config\SslConfig;
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;
use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Static\Utils;
use InvalidArgumentException;
use Stringable;

/**
 * cURLで送信するリクエスト内容を表す値オブジェクト。
 */
final class Request {

    /** @var string Requestを識別するID */
    public string $id;

    /**
     * @param string              $url            送信先URL
     * @param CurlMethod          $method         HTTPメソッド
     * @param bool                $captureBody    レスポンスボディをResponseに保持するか
     * @param bool                $captureHeaders レスポンスヘッダーをResponseに保持するか
     * @param array<string,mixed> $requestHeaders 送信するHTTPヘッダー
     * @param ProxyConfig|null    $proxy          プロキシー設定
     * @param SslConfig|null      $ssl            SSL/TLS設定
     * @param AuthConfig|null     $auth           認証設定
     * @param TimeoutConfig|null  $timeout        タイムアウト設定
     * @param RedirectConfig|null $redirect       リダイレクト設定
     */
    public function __construct(
        public string          $url,
        public CurlMethod      $method = CurlMethod::GET,
        public bool            $captureBody = true,
        public bool            $captureHeaders = true,
        public array           $requestHeaders = [],
        public ?ProxyConfig    $proxy = null,
        public ?SslConfig      $ssl = null,
        public ?AuthConfig     $auth = null,
        public ?TimeoutConfig  $timeout = null,
        public ?RedirectConfig $redirect = null,
    ){
        $this->id = Utils::uuid_v4();
        $this->url = self::validateUrl($this->url);
        $this->requestHeaders = self::validateHeaders($this->requestHeaders);
    }

    /**
     * Request::get('https://example.com') のようなHTTPメソッド名の静的Factoryを提供する。
     *
     * @param  string $method 呼び出された静的メソッド名
     * @param  array  $args   Requestコンストラクタへ渡す引数
     * @return self
     */
    public static function __callStatic(string $method, array $args): mixed {

        $curlMethod = self::findMethod($method);
        if($curlMethod === null){
            throw new InvalidArgumentException(sprintf('Invalid method: %s', $method));
        }

        $url = null;
        if(isset($args['url']) && is_string($args['url'])){
            $url = $args['url'];
            unset($args['url']);
        } else if(isset($args[0]) && is_string($args[0])){
            $url = $args[0];
            unset($args[0]);
        }

        if(!isset($url)){
            throw new InvalidArgumentException('Request URL is required.');
        }

        return new self($url, $curlMethod, ...$args);
    }

    /**
     * Requestに設定されたConfig群を返す。
     *
     * @return array<int, ProxyConfig|SslConfig|AuthConfig|TimeoutConfig|RedirectConfig|null>
     */
    public function getConfig(): array {
        return [
            $this->proxy,
            $this->ssl,
            $this->auth,
            $this->timeout,
            $this->redirect,
        ];
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
    private static function findMethod(string $method): ?CurlMethod {

        $method = strtolower($method);
        foreach(CurlMethod::cases() as $curlMethod){
            if(strtolower($curlMethod->name) === $method){
                return $curlMethod;
            }
        }

        return null;
    }
}
