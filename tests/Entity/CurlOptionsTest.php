<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Entity;

use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * CurlOptionsのイミュータブルなhelper APIを検証する。
 */
final class CurlOptionsTest extends TestCase {

    /**
     * fluent helperが元インスタンスを変更せず、新しいインスタンスを返すことを検証する。
     *
     * @return void
     */
    public function testFluentHelpersReturnNewInstances(): void {

        $base = CurlOptions::create();
        $withoutHeaders = $base->captureHeaders(false);
        $withoutBody = $base->captureBody(false);
        $timeout = $base->timeout(15);
        $redirect = $base->followRedirects(maxRedirects: 3, autoReferer: false);
        $client = $base
            ->userAgent('php-simple-curl-test/1.0')
            ->referer('https://example.com/from');

        self::assertNotSame($base, $withoutHeaders);
        self::assertNotSame($base, $withoutBody);
        self::assertNotSame($base, $timeout);
        self::assertNotSame($base, $redirect);
        self::assertNotSame($base, $client);

        self::assertTrue($base->captureHeaders);
        self::assertTrue($base->captureBody);
        self::assertNull($base->timeout);
        self::assertNull($base->redirect);
        self::assertNull($base->client);

        self::assertFalse($withoutHeaders->captureHeaders);
        self::assertTrue($withoutHeaders->captureBody);

        self::assertTrue($withoutBody->captureHeaders);
        self::assertFalse($withoutBody->captureBody);

        self::assertInstanceOf(TimeoutConfig::class, $timeout->timeout);
        self::assertSame(15, $timeout->timeout->timeoutSeconds);
        self::assertSame(15, $timeout->timeout->connectTimeoutSeconds);

        self::assertInstanceOf(RedirectConfig::class, $redirect->redirect);
        self::assertTrue($redirect->redirect->follow);
        self::assertSame(3, $redirect->redirect->maxRedirects);
        self::assertFalse($redirect->redirect->autoReferer);

        self::assertSame('php-simple-curl-test/1.0', $client->client->userAgent);
        self::assertSame('https://example.com/from', $client->client->referer);
    }

    /**
     * followRedirect()がfollowRedirects()のデフォルト設定と同じ結果になることを検証する。
     *
     * @return void
     */
    public function testFollowRedirectAliasMatchesDefaultFollowRedirects(): void {

        $options = CurlOptions::create()->followRedirect();

        self::assertInstanceOf(RedirectConfig::class, $options->redirect);
        self::assertTrue($options->redirect->follow);
        self::assertSame(10, $options->redirect->maxRedirects);
        self::assertTrue($options->redirect->autoReferer);
    }

    /**
     * 空のUser-Agentを不正として扱うことを検証する。
     *
     * @return void
     */
    public function testUserAgentRejectsEmptyValue(): void {

        $this->expectException(InvalidArgumentException::class);

        CurlOptions::create()->userAgent(' ');
    }

    /**
     * 空のRefererを不正として扱うことを検証する。
     *
     * @return void
     */
    public function testRefererRejectsEmptyValue(): void {

        $this->expectException(InvalidArgumentException::class);

        CurlOptions::create()->referer(' ');
    }
}
