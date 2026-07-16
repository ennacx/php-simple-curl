<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Option;

use Ennacx\SimpleCurl\Config\ClientConfig;
use Ennacx\SimpleCurl\Config\RedirectConfig;
use Ennacx\SimpleCurl\Config\TimeoutConfig;
use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;
use Ennacx\SimpleCurl\Option\CurlOptions;
use Ennacx\SimpleCurl\Option\RawCurlOptions;
use PHPUnit\Framework\TestCase;

/**
 * CurlOptionsのイミュータブルなhelper APIを検証する。
 */
final class CurlOptionsTest extends TestCase {

    /**
     * fluent helperが元インスタンスを変更せず、新しいインスタンスを返すことを検証する。
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

        self::assertTrue($base->isCapturingHeaders());
        self::assertTrue($base->isCapturingBody());
        self::assertFalse($base->has(TimeoutConfig::class));
        self::assertFalse($base->has(RedirectConfig::class));
        self::assertFalse($base->has(ClientConfig::class));

        self::assertFalse($withoutHeaders->isCapturingHeaders());
        self::assertTrue($withoutHeaders->isCapturingBody());

        self::assertTrue($withoutBody->isCapturingHeaders());
        self::assertFalse($withoutBody->isCapturingBody());

        $timeoutConfig = $timeout->get(TimeoutConfig::class);
        self::assertInstanceOf(TimeoutConfig::class, $timeoutConfig);
        self::assertSame(15, $timeoutConfig->timeoutSeconds);
        self::assertSame(15, $timeoutConfig->connectTimeoutSeconds);

        $redirectConfig = $redirect->get(RedirectConfig::class);
        self::assertInstanceOf(RedirectConfig::class, $redirectConfig);
        self::assertTrue($redirectConfig->follow);
        self::assertSame(3, $redirectConfig->maxRedirects);
        self::assertFalse($redirectConfig->autoReferer);

        $clientConfig = $client->get(ClientConfig::class);
        self::assertInstanceOf(ClientConfig::class, $clientConfig);
        self::assertSame('php-simple-curl-test/1.0', $clientConfig->userAgent);
        self::assertSame('https://example.com/from', $clientConfig->referer);
    }

    /**
     * followRedirect()がfollowRedirects()のデフォルト設定と同じ結果になることを検証する。
     */
    public function testFollowRedirectAliasMatchesDefaultFollowRedirects(): void {

        $options = CurlOptions::create()->followRedirect();

        $redirectConfig = $options->get(RedirectConfig::class);
        self::assertInstanceOf(RedirectConfig::class, $redirectConfig);
        self::assertTrue($redirectConfig->follow);
        self::assertSame(10, $redirectConfig->maxRedirects);
        self::assertTrue($redirectConfig->autoReferer);
    }

    /**
     * remove()が元インスタンスを変更せず、指定Configを除外した新しいインスタンスを返すことを検証する。
     */
    public function testRemoveReturnsNewInstanceWithoutSpecifiedConfig(): void {

        $options = CurlOptions::create()
            ->timeout(10)
            ->followRedirects();

        $removed = $options->remove(TimeoutConfig::class);

        self::assertNotSame($options, $removed);
        self::assertTrue($options->has(TimeoutConfig::class));
        self::assertFalse($removed->has(TimeoutConfig::class));
        self::assertTrue($removed->has(RedirectConfig::class));
    }

    /**
     * get()は必須Config取得、find()は任意Config取得として扱えることを検証する。
     */
    public function testGetThrowsAndFindReturnsNullForMissingConfig(): void {

        $options = CurlOptions::create()
            ->timeout(10);

        self::assertInstanceOf(TimeoutConfig::class, $options->get(TimeoutConfig::class));
        self::assertNull($options->find(RedirectConfig::class));

        $this->expectException(InvalidConfigurationException::class);

        $options->get(RedirectConfig::class);
    }

    /**
     * raw()でRawCurlOptionsを追加でき、通常Config一覧からは除外されることを検証する。
     */
    public function testRawAddsRawCurlOptionsAndExcludesItFromNormalConfigList(): void {

        $options = CurlOptions::create()
            ->timeout(10)
            ->raw([
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
            ]);

        self::assertTrue($options->has(RawCurlOptions::class));
        self::assertInstanceOf(RawCurlOptions::class, $options->get(RawCurlOptions::class));
        self::assertSame(CURL_HTTP_VERSION_2TLS, $options->get(RawCurlOptions::class)->get(CURLOPT_HTTP_VERSION));
        self::assertContainsOnlyInstancesOf(TimeoutConfig::class, $options->getConfig());
    }

    /**
     * 空のUser-Agentを不正として扱うことを検証する。
     */
    public function testUserAgentRejectsEmptyValue(): void {

        $this->expectException(InvalidConfigurationException::class);

        CurlOptions::create()->userAgent(' ');
    }

    /**
     * 空のRefererを不正として扱うことを検証する。
     */
    public function testRefererRejectsEmptyValue(): void {

        $this->expectException(InvalidConfigurationException::class);

        CurlOptions::create()->referer(' ');
    }
}
