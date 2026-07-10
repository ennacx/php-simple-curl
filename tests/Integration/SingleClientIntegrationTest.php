<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Integration;

use Ennacx\SimpleCurl\Client\SingleClient;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\Request;
use Throwable;

/**
 * SingleClientがローカルHTTP serverへ実リクエストできることを検証する。
 */
final class SingleClientIntegrationTest extends LocalHttpServerTestCase {

    /**
     * JSONレスポンスを取得し、ステータス・ヘッダー・JSON helperが連動することを検証する。
     *
     * @return void
     * @throws Throwable
     */
    public function testSendReturnsJsonResponseFromLocalServer(): void {

        $configuredRequest = Request::get(self::url('/json'))
            ->headers(['Accept' => 'application/json'])
            ->withOptions(
                CurlOptions::create()
                    ->timeout(5)
            );

        $response = (new SingleClient())->send($configuredRequest);

        self::assertNull($response->error);
        self::assertTrue($response->isOk());
        self::assertTrue($response->isSuccessful());
        self::assertTrue($response->hasHeader('content-type'));
        self::assertStringContainsString('application/json', (string)$response->header('content-type'));
        self::assertSame([
            'ok' => true,
            'method' => 'GET',
            'accept' => 'application/json',
        ], $response->json());
    }

    /**
     * followRedirects()を有効にした場合にリダイレクト先のレスポンスを取得できることを検証する。
     *
     * @return void
     * @throws Throwable
     */
    public function testSendFollowsRedirectWhenEnabled(): void {

        $configuredRequest = Request::get(self::url('/redirect'))
            ->withOptions(
                CurlOptions::create()
                    ->timeout(5)
                    ->followRedirects()
            );

        $response = (new SingleClient())->send($configuredRequest);

        self::assertNull($response->error);
        self::assertTrue($response->isOk());
        self::assertSame(200, $response->statusCode);
        self::assertSame('GET', $response->json()['method']);
    }

    /**
     * ボディを取得せず、レスポンスヘッダーだけを保持できることを検証する。
     *
     * @return void
     * @throws Throwable
     */
    public function testSendCanCaptureHeadersWithoutBody(): void {

        $configuredRequest = Request::get(self::url('/text'))
            ->withOptions(
                CurlOptions::create()
                    ->captureBody(false)
                    ->captureHeaders(true)
                    ->timeout(5)
            );

        $response = (new SingleClient())->send($configuredRequest);

        self::assertNull($response->error);
        self::assertTrue($response->isOk());
        self::assertNull($response->body);
        self::assertNotSame([], $response->headers);
        self::assertTrue($response->hasHeader('content-type'));
    }

    /**
     * HTTP 4xxがcURLエラーではなくResponse上のHTTPエラーとして扱われることを検証する。
     *
     * @return void
     * @throws Throwable
     */
    public function testSendReturnsHttpErrorResponse(): void {

        $configuredRequest = Request::get(self::url('/status/404'))
            ->withOptions(
                CurlOptions::create()
                    ->timeout(5)
            );

        $response = (new SingleClient())->send($configuredRequest);

        self::assertNull($response->error);
        self::assertSame(404, $response->statusCode);
        self::assertTrue($response->isClientError());
        self::assertTrue($response->isError());
        self::assertFalse($response->isSuccessful());
        self::assertSame(['error' => 'not_found'], $response->json());
    }
}
