<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Factory;

use Ennacx\SimpleCurl\Entity\Config\AuthConfig;
use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Enum\RequestContentType;
use Ennacx\SimpleCurl\Factory\CurlOptionsFactory;
use PHPUnit\Framework\TestCase;

/**
 * CurlOptionsFactoryがConfiguredRequestをcURLオプション配列へ変換する処理を検証する。
 */
final class CurlOptionsFactoryTest extends TestCase {

    /**
     * デフォルト設定のConfiguredRequestから基本cURLオプションを生成できることを検証する。
     *
     * @return void
     */
    public function testBuildsDefaultOptionsFromConfiguredRequest(): void {

        $configuredRequest = Request::get('https://example.com')
            ->headers(['Accept' => 'text/html'])
            ->asConfigured();

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame('https://example.com', $options[CURLOPT_URL]);
        self::assertTrue($options[CURLOPT_RETURNTRANSFER]);
        self::assertTrue($options[CURLOPT_HEADER]);
        self::assertTrue($options[CURLOPT_HTTPGET]);
        self::assertSame(['Accept: text/html'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * URL作成時点のクエリと追加クエリがCURLOPT_URLへ反映されることを検証する。
     *
     * @return void
     */
    public function testBuildsUrlWithQueryParameters(): void {

        $configuredRequest = Request::get('https://example.com/search?b=2&a=1')
            ->param('c', '3')
            ->asConfigured();

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);
        $urlParts = self::parseUrlWithQuery($options[CURLOPT_URL]);

        self::assertSame('https', $urlParts['scheme']);
        self::assertSame('example.com', $urlParts['host']);
        self::assertSame('/search', $urlParts['path']);
        self::assertSame([
            'b' => '2',
            'a' => '1',
            'c' => '3',
        ], $urlParts['query']);
    }

    /**
     * フラグメント付きURLでもクエリがフラグメントより前に付与されることを検証する。
     *
     * @return void
     */
    public function testBuildsUrlWithQueryParametersBeforeFragment(): void {

        $configuredRequest = Request::get('https://example.com/path?foo=1#section')
            ->params([
                'bar' => '2',
                'baz' => '3',
            ])
            ->asConfigured();

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);
        $urlParts = self::parseUrlWithQuery($options[CURLOPT_URL]);

        self::assertSame('https', $urlParts['scheme']);
        self::assertSame('example.com', $urlParts['host']);
        self::assertSame('/path', $urlParts['path']);
        self::assertSame('section', $urlParts['fragment']);
        self::assertSame([
            'foo' => '1',
            'bar' => '2',
            'baz' => '3',
        ], $urlParts['query']);
    }

    /**
     * ボディとヘッダーの取得が不要な場合にCURLOPT_RETURNTRANSFERが無効になることを検証する。
     *
     * @return void
     */
    public function testDisablesReturnTransferWhenBodyAndHeadersAreNotCaptured(): void {

        $configuredRequest = Request::get('https://example.com')
            ->withOptions(
                CurlOptions::create()
                    ->captureBody(false)
                    ->captureHeaders(false)
            );

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertFalse($options[CURLOPT_RETURNTRANSFER]);
        self::assertFalse($options[CURLOPT_HEADER]);
    }

    /**
     * Timeout、Redirect、Bearer認証がcURLオプションと送信ヘッダーへ反映されることを検証する。
     *
     * @return void
     */
    public function testAppliesTimeoutRedirectAndAuthHeaders(): void {

        $configuredRequest = Request::get('https://example.com')
            ->headers(['Accept' => 'application/json'])
            ->withOptions(CurlOptions::create(
                AuthConfig::bearer('token'),
                TimeoutConfig::seconds(timeoutSec: 9, connectTimeoutSec: 4),
                RedirectConfig::enabled(maxRedirects: 2, autoReferer: false),
            ));

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame(9, $options[CURLOPT_TIMEOUT]);
        self::assertSame(4, $options[CURLOPT_CONNECTTIMEOUT]);
        self::assertTrue($options[CURLOPT_FOLLOWLOCATION]);
        self::assertSame(2, $options[CURLOPT_MAXREDIRS]);
        self::assertFalse($options[CURLOPT_AUTOREFERER]);
        self::assertSame([
            'Accept: application/json',
            'Authorization: Bearer token',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * User-AgentとRefererが送信ヘッダーへ反映されることを検証する。
     *
     * @return void
     */
    public function testAppliesClientHeaders(): void {

        $configuredRequest = Request::get('https://example.com')
            ->withOptions(
                CurlOptions::create()
                    ->userAgent('php-simple-curl-test/1.0')
                    ->referer('https://example.com/from')
            );

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame([
            'User-Agent: php-simple-curl-test/1.0',
            'Referer: https://example.com/from',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * Requestで明示されたUser-AgentとRefererはClientConfigで上書きしないことを検証する。
     *
     * @return void
     */
    public function testKeepsUserDefinedClientHeaders(): void {

        $configuredRequest = Request::get('https://example.com')
            ->headers([
                'User-Agent' => 'custom-agent/2.0',
                'Referer'    => 'https://example.com/custom',
            ])
            ->withOptions(
                CurlOptions::create()
                    ->userAgent('php-simple-curl-test/1.0')
                    ->referer('https://example.com/from')
            );

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame([
            'User-Agent: custom-agent/2.0',
            'Referer: https://example.com/custom',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * プレーンテキストのリクエストボディがcURLオプションへ反映されることを検証する。
     *
     * @return void
     */
    public function testBuildsPlainTextRequestBodyOptions(): void {

        $configuredRequest = Request::post('https://example.com/messages')
            ->body('plain text message', RequestContentType::PlainText)
            ->asConfigured();

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame('plain text message', $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: text/plain'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * ファイル内容をリクエストボディとしてcURLオプションへ反映できることを検証する。
     *
     * @return void
     */
    public function testBuildsRequestBodyOptionsFromFile(): void {

        // テンポラリに一時ファイルを生成
        $path = tempnam(sys_get_temp_dir(), 'simple-curl-body-');
        self::assertIsString($path);
        file_put_contents($path, "line 1\nline 2\n");

        try{
            $configuredRequest = Request::post('https://example.com/upload')
                ->bodyFromFile($path, RequestContentType::PlainText)
                ->asConfigured();

            $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

            self::assertSame("line 1\nline 2\n", $options[CURLOPT_POSTFIELDS]);
            self::assertSame(['Content-Type: text/plain'], $options[CURLOPT_HTTPHEADER]);
        } finally{
            unlink($path);
        }
    }

    /**
     * `0` のようなempty判定される文字列でもリクエストボディとして扱えることを検証する。
     *
     * @return void
     */
    public function testBuildsZeroStringRequestBodyOptions(): void {

        $configuredRequest = Request::post('https://example.com/messages')
            ->body('0')
            ->asConfigured();

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame('0', $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: text/plain'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * JSONリクエストボディがJSON文字列とContent-Typeへ変換されることを検証する。
     *
     * @return void
     */
    public function testBuildsJsonRequestBodyOptions(): void {

        $configuredRequest = Request::post('https://example.com/users')
            ->json([
                'name' => 'Taro',
                'tags' => ['php', 'curl'],
            ])
            ->asConfigured();

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame('{"name":"Taro","tags":["php","curl"]}', $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: application/json'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * JSON文字列をそのままJSONリクエストボディとして扱えることを検証する。
     *
     * @return void
     */
    public function testBuildsJsonRequestBodyOptionsFromJsonString(): void {

        $json = '{"name":"Taro","tags":["php","curl"]}';
        $configuredRequest = Request::post('https://example.com/users')
            ->json($json)
            ->asConfigured();

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame($json, $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: application/json'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * フォームリクエストボディがURLエンコード済み文字列とContent-Typeへ変換されることを検証する。
     *
     * @return void
     */
    public function testBuildsFormRequestBodyOptions(): void {

        $configuredRequest = Request::post('https://example.com/token')
            ->form([
                'grant_type' => 'client_credentials',
                'client_id' => 'example-client',
            ])
            ->asConfigured();

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame('grant_type=client_credentials&client_id=example-client', $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: application/x-www-form-urlencoded'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * ユーザーが指定したContent-Typeはリクエストボディの既定値で上書きしないことを検証する。
     *
     * @return void
     */
    public function testKeepsUserDefinedContentTypeForRequestBody(): void {

        $configuredRequest = Request::post('https://example.com/users')
            ->headers(['Content-Type' => 'application/vnd.api+json'])
            ->json(['name' => 'Taro'])
            ->asConfigured();

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame('{"name":"Taro"}', $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: application/vnd.api+json'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * `X-Content-Type-Options` はContent-Type指定とは扱わないことを検証する。
     *
     * @return void
     */
    public function testAddsDefaultContentTypeWhenOnlyContentTypeOptionsHeaderExists(): void {

        $configuredRequest = Request::post('https://example.com/users')
            ->headers(['X-Content-Type-Options' => 'nosniff'])
            ->json(['name' => 'Taro'])
            ->asConfigured();

        $options = (new CurlOptionsFactory())->fromConfiguredRequest($configuredRequest);

        self::assertSame('{"name":"Taro"}', $options[CURLOPT_POSTFIELDS]);
        self::assertSame([
            'X-Content-Type-Options: nosniff',
            'Content-Type: application/json',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * URLをパースし、クエリ文字列を連想配列へ正規化する。
     *
     * @param  string $url
     * @return array{scheme: string, host: string, path: string, query: array<string, mixed>, fragment?: string}
     */
    private static function parseUrlWithQuery(string $url): array {

        $parts = parse_url($url);
        self::assertIsArray($parts);

        $query = [];
        if(isset($parts['query'])){
            parse_str($parts['query'], $query);
        }

        return [
            'scheme'   => $parts['scheme'] ?? '',
            'host'     => $parts['host'] ?? '',
            'path'     => $parts['path'] ?? '',
            'query'    => $query,
            'fragment' => $parts['fragment'] ?? null,
        ];
    }
}
