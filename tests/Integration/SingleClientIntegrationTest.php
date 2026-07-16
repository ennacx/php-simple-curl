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
     * @throws Throwable
     */
    public function testSendReturnsJsonResponseFromLocalServer(): void {

        $preparedRequest = Request::get(self::url('/json'))
            ->headers(['Accept' => 'application/json'])
            ->prepare(
                CurlOptions::create()
                    ->timeout(5)
            );

        $response = (new SingleClient())->send($preparedRequest);

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
     * Requestを直接渡した場合に、内部でPreparedRequestへ変換されて実行できることを検証する。
     *
     * @throws Throwable
     */
    public function testSendAcceptsRequestAndPreparesItInternally(): void {

        $request = Request::get(self::url('/json'))
            ->headers(['Accept' => 'application/json']);

        $response = (new SingleClient())->send($request);

        self::assertNull($response->error);
        self::assertTrue($response->isSuccessful());
        self::assertSame([
            'ok' => true,
            'method' => 'GET',
            'accept' => 'application/json',
        ], $response->json());
    }

    /**
     * followRedirects()を有効にした場合にリダイレクト先のレスポンスを取得できることを検証する。
     *
     * @throws Throwable
     */
    public function testSendFollowsRedirectWhenEnabled(): void {

        $preparedRequest = Request::get(self::url('/redirect'))
            ->prepare(
                CurlOptions::create()
                    ->timeout(5)
                    ->followRedirects()
            );

        $response = (new SingleClient())->send($preparedRequest);

        self::assertNull($response->error);
        self::assertTrue($response->isOk());
        self::assertSame(200, $response->statusCode);
        self::assertSame('GET', $response->json()['method']);
    }

    /**
     * ボディを取得せず、レスポンスヘッダーだけを保持できることを検証する。
     *
     * @throws Throwable
     */
    public function testSendCanCaptureHeadersWithoutBody(): void {

        $preparedRequest = Request::get(self::url('/text'))
            ->prepare(
                CurlOptions::create()
                    ->captureBody(false)
                    ->captureHeaders(true)
                    ->timeout(5)
            );

        $response = (new SingleClient())->send($preparedRequest);

        self::assertNull($response->error);
        self::assertTrue($response->isOk());
        self::assertNull($response->body);
        self::assertNotSame([], $response->rawHeaders());
        self::assertTrue($response->hasHeader('content-type'));
    }

    /**
     * HTTP 4xxがcURLエラーではなくResponse上のHTTPエラーとして扱われることを検証する。
     *
     * @throws Throwable
     */
    public function testSendReturnsHttpErrorResponse(): void {

        $preparedRequest = Request::get(self::url('/status/404'))
            ->prepare(
                CurlOptions::create()
                    ->timeout(5)
            );

        $response = (new SingleClient())->send($preparedRequest);

        self::assertNull($response->error);
        self::assertSame(404, $response->statusCode);
        self::assertTrue($response->isClientError());
        self::assertTrue($response->isError());
        self::assertFalse($response->isSuccessful());
        self::assertSame(['error' => 'not_found'], $response->json());
    }
}
