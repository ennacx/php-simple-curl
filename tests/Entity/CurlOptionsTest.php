<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Entity;

use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;
use Ennacx\SimpleCurl\Entity\CurlOptions;
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

        self::assertNotSame($base, $withoutHeaders);
        self::assertNotSame($base, $withoutBody);
        self::assertNotSame($base, $timeout);
        self::assertNotSame($base, $redirect);

        self::assertTrue($base->captureHeaders);
        self::assertTrue($base->captureBody);
        self::assertNull($base->timeout);
        self::assertNull($base->redirect);

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
}
