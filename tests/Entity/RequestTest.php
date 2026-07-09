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
     * URLに含まれる既存クエリがRequest生成時にqueryParamsへ分離されることを検証する。
     *
     * @return void
     */
    public function testExistingQueryStringIsParsedOnCreate(): void {

        $request = Request::get('https://example.com/search?b=2&a=1');

        self::assertSame('https://example.com/search', $request->url);
        self::assertSame([
            'b' => '2',
            'a' => '1',
        ], $request->queryParams);
    }

    /**
     * param()でクエリの追加・上書き・削除ができ、元のRequestは変更されないことを検証する。
     *
     * @return void
     */
    public function testParamAddsOverwritesAndRemovesQueryParameter(): void {

        $request = Request::get('https://example.com?keep=1&remove=2');
        $updated = $request
            ->param('added', ' 3 ')
            ->param('keep', 9)
            ->param('remove', null);

        self::assertSame([
            'keep' => '1',
            'remove' => '2',
        ], $request->queryParams);
        self::assertSame([
            'keep' => '9',
            'added' => '3',
        ], $updated->queryParams);
    }

    /**
     * params()で複数クエリを追加でき、overwrite=falseでは既存値を維持することを検証する。
     *
     * @return void
     */
    public function testParamsAddsQueryParametersAndCanKeepExistingValues(): void {

        $request = Request::get('https://example.com?keep=1');
        $updated = $request->params([
            'keep' => '2',
            'new' => '3',
            0 => 'ignored',
        ], overwrite: false);

        self::assertSame(['keep' => '1'], $request->queryParams);
        self::assertSame([
            'keep' => '1',
            'new' => '3',
        ], $updated->queryParams);
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
