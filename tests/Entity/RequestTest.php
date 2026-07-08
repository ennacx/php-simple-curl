<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Entity;

use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\PendingRequest;
use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Enum\CurlMethod;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Requestの生成、入力検証、PendingRequest化を検証する。
 */
final class RequestTest extends TestCase {

    /**
     * HTTPメソッド名の静的FactoryからRequestを生成できることを検証する。
     *
     * @return void
     */
    public function testStaticFactoryCreatesRequestWithHttpMethod(): void {

        $request = Request::post('https://example.com');

        self::assertSame('https://example.com', $request->url);
        self::assertSame(CurlMethod::POST, $request->method);
        self::assertNotSame('', $request->id);
    }

    /**
     * 送信ヘッダーの値が文字列へ正規化されることを検証する。
     *
     * @return void
     */
    public function testHeadersAreNormalized(): void {

        $request = Request::get('https://example.com')
            ->headers([
                'Accept' => ' application/json ',
                'X-Number' => 123,
            ]);

        self::assertSame([
            'Accept' => 'application/json',
            'X-Number' => '123',
        ], $request->requestHeaders);
    }

    /**
     * スキームのないURLを不正として扱うことを検証する。
     *
     * @return void
     */
    public function testInvalidUrlThrowsException(): void {

        $this->expectException(InvalidArgumentException::class);

        Request::get('example.com');
    }

    /**
     * 空のヘッダー名を不正として扱うことを検証する。
     *
     * @return void
     */
    public function testInvalidHeaderNameThrowsException(): void {

        $this->expectException(InvalidArgumentException::class);

        Request::get('https://example.com')->headers(['' => 'value']);
    }

    /**
     * CurlOptions付きのPendingRequestを生成できることを検証する。
     *
     * @return void
     */
    public function testWithOptionsCreatesPendingRequest(): void {

        $request = Request::get('https://example.com');
        $options = CurlOptions::create()->timeout(10);
        $pendingRequest = $request->withOptions($options);

        self::assertInstanceOf(PendingRequest::class, $pendingRequest);
        self::assertSame($request, $pendingRequest->request);
        self::assertSame($options, $pendingRequest->options);
    }

    /**
     * CurlOptionsなしのPendingRequestを生成できることを検証する。
     *
     * @return void
     */
    public function testAsPendingCreatesPendingRequestWithDefaultOptions(): void {

        $request = Request::get('https://example.com');
        $pendingRequest = $request->asPending();

        self::assertSame($request, $pendingRequest->request);
        self::assertNull($pendingRequest->options);
    }
}
