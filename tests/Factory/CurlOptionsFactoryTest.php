<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Factory;

use Ennacx\SimpleCurl\Entity\Config\AuthConfig;
use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Factory\CurlOptionsFactory;
use PHPUnit\Framework\TestCase;

/**
 * CurlOptionsFactoryがPendingRequestをcURLオプション配列へ変換する処理を検証する。
 */
final class CurlOptionsFactoryTest extends TestCase {

    /**
     * デフォルト設定のPendingRequestから基本cURLオプションを生成できることを検証する。
     *
     * @return void
     */
    public function testBuildsDefaultOptionsFromPendingRequest(): void {

        $pendingRequest = Request::get('https://example.com')
            ->headers(['Accept' => 'text/html'])
            ->asPending();

        $options = (new CurlOptionsFactory())->fromPendingRequest($pendingRequest);

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

        $pendingRequest = Request::get('https://example.com/search?b=2&a=1')
            ->param('c', '3')
            ->asPending();

        $options = (new CurlOptionsFactory())->fromPendingRequest($pendingRequest);
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

        $pendingRequest = Request::get('https://example.com/path?foo=1#section')
            ->params([
                'bar' => '2',
                'baz' => '3',
            ])
            ->asPending();

        $options = (new CurlOptionsFactory())->fromPendingRequest($pendingRequest);
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

        $pendingRequest = Request::get('https://example.com')
            ->withOptions(
                CurlOptions::create()
                    ->captureBody(false)
                    ->captureHeaders(false)
            );

        $options = (new CurlOptionsFactory())->fromPendingRequest($pendingRequest);

        self::assertFalse($options[CURLOPT_RETURNTRANSFER]);
        self::assertFalse($options[CURLOPT_HEADER]);
    }

    /**
     * Timeout、Redirect、Bearer認証がcURLオプションと送信ヘッダーへ反映されることを検証する。
     *
     * @return void
     */
    public function testAppliesTimeoutRedirectAndAuthHeaders(): void {

        $pendingRequest = Request::get('https://example.com')
            ->headers(['Accept' => 'application/json'])
            ->withOptions(new CurlOptions(
                auth: AuthConfig::bearer('token'),
                timeout: TimeoutConfig::seconds(timeoutSec: 9, connectTimeoutSec: 4),
                redirect: RedirectConfig::enabled(maxRedirects: 2, autoReferer: false),
            ));

        $options = (new CurlOptionsFactory())->fromPendingRequest($pendingRequest);

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
