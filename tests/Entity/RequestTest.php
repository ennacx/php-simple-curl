<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Entity;

use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\PreparedRequest;
use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Enum\RequestContentType;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * Requestの生成、入力検証、PreparedRequest化を検証する。
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
                'Accept'   => ' application/json ',
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
            'keep'   => '1',
            'remove' => '2',
        ], $request->queryParams);
        self::assertSame([
            'keep'  => '9',
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
            'new'  => '3',
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
     * ファイル内容をリクエストボディーとして設定でき、元のRequestを変更しないことを検証する。
     *
     * @return void
     */
    public function testBodyFromFileCreatesRequestBodyFromReadableFile(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-request-');
        self::assertIsString($path);

        file_put_contents($path, "file body\n");

        try{
            $request = Request::post('https://example.com/upload');
            $updated = $request->bodyFromFile($path, RequestContentType::PlainText);

            self::assertNull($request->requestBody);
            self::assertNull($request->requestContentType);
            self::assertSame("file body\n", $updated->requestBody);
            self::assertSame(RequestContentType::PlainText, $updated->requestContentType);
        } finally{
            unlink($path);
        }
    }

    /**
     * 存在しないファイルをリクエストボディーに指定した場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testBodyFromFileThrowsExceptionForMissingFile(): void {

        $this->expectException(InvalidArgumentException::class);

        Request::post('https://example.com/upload')
            ->bodyFromFile(sys_get_temp_dir() . '/simple-curl-missing-file');
    }

    /**
     * 不正なJSON文字列はthrow=trueの場合に例外を投げることを検証する。
     *
     * @return void
     * @throws JsonException
     */
    public function testJsonThrowsExceptionForInvalidJsonString(): void {

        $this->expectException(JsonException::class);

        Request::post('https://example.com/users')
            ->json('{"name":');
    }

    /**
     * 不正なJSON文字列でもthrow=falseの場合は元のRequestを返すことを検証する。
     *
     * @return void
     */
    public function testJsonReturnsOriginalRequestForInvalidJsonStringWhenThrowDisabled(): void {

        $request = Request::post('https://example.com/users');
        $updated = $request->json('{"name":', throw: false);

        self::assertSame($request, $updated);
        self::assertNull($updated->requestBody);
        self::assertNull($updated->requestContentType);
    }

    /**
     * CurlOptions付きのPreparedRequestを生成できることを検証する。
     *
     * @return void
     */
    public function testPrepareCreatesPreparedRequestWithOptions(): void {

        $request = Request::get('https://example.com');
        $options = CurlOptions::create()->timeout(10);
        $preparedRequest = $request->prepare($options);

        self::assertInstanceOf(PreparedRequest::class, $preparedRequest);
        self::assertSame($request, $preparedRequest->request);
        self::assertSame($options, $preparedRequest->options);
    }

    /**
     * CurlOptionsなしのPreparedRequestを生成できることを検証する。
     *
     * @return void
     */
    public function testPrepareCreatesPreparedRequestWithDefaultOptions(): void {

        $request = Request::get('https://example.com');
        $preparedRequest = $request->prepare();

        self::assertSame($request, $preparedRequest->request);
        self::assertNull($preparedRequest->options);
    }
}
