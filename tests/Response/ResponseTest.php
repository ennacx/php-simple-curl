<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Response;

use Ennacx\SimpleCurl\Enum\CurlError;
use Ennacx\SimpleCurl\Exception\InvalidResponseException;
use Ennacx\SimpleCurl\Response\Response;
use PHPUnit\Framework\TestCase;

/**
 * Responseのステータス判定、ヘッダー参照、JSON helperを検証する。
 */
final class ResponseTest extends TestCase {

    /**
     * HTTPステータス別のhelper判定が期待通り分類されることを検証する。
     */
    public function testStatusHelpersClassifyResponses(): void {

        $ok = new Response(200, [], null, [], null, '');
        $created = new Response(201, [], null, [], null, '');
        $redirect = new Response(302, [], null, [], null, '');
        $clientError = new Response(404, [], null, [], null, '');
        $serverError = new Response(500, [], null, [], null, '');

        self::assertTrue($ok->isOk());
        self::assertTrue($ok->isSuccessful());

        self::assertFalse($created->isOk());
        self::assertTrue($created->isSuccessful());

        self::assertTrue($redirect->isRedirect());
        self::assertFalse($redirect->isSuccessful());
        self::assertFalse($redirect->isError());

        self::assertTrue($clientError->isClientError());
        self::assertTrue($clientError->isError());

        self::assertTrue($serverError->isServerError());
        self::assertTrue($serverError->isError());
    }

    /**
     * レスポンスヘッダーを小文字キーで参照でき、重複ヘッダーは配列化されることを検証する。
     */
    public function testHeaderHelpersParseCaseInsensitiveHeaders(): void {

        $responseHeader = [
            'HTTP/1.1 200 OK',
            'Content-Type: application/json',
            'Set-Cookie: a=1',
            'Set-Cookie: b=2',
        ];

        $response = new Response(
            statusCode:   200,
            headers:      $responseHeader,
            body:         null,
            info:         [],
            error:        null,
            errorMessage: '',
        );

        self::assertSame([
            'HTTP/1.1 200 OK',
            'Content-Type: application/json',
            'Set-Cookie: a=1',
            'Set-Cookie: b=2',
        ], $response->rawHeaders());
        self::assertTrue($response->hasHeader('content-type'));
        self::assertTrue($response->hasHeader('Content-Type'));
        self::assertSame('application/json', $response->header('CONTENT-TYPE'));
        self::assertSame(['a=1', 'b=2'], $response->header('set-cookie'));
        self::assertNull($response->header('x-missing'));
        self::assertSame('application/json', $response->headers()['content-type']);
    }

    /**
     * JSON文字列のレスポンスボディを配列へデコードできることを検証する。
     */
    public function testJsonDecodesBody(): void {

        $response = new Response(200, [], '{"ok":true}', [], null, '');

        self::assertSame(['ok' => true], $response->json());
    }

    /**
     * 空ボディでもthrowを無効にするとnullを返すことを検証する。
     */
    public function testJsonReturnsNullForEmptyBodyWhenThrowIsDisabled(): void {

        $response = new Response(204, [], null, [], null, '');

        self::assertNull($response->json(throw: false));
    }

    /**
     * 空ボディをJSONとして読む場合、デフォルトでは例外を投げることを検証する。
     */
    public function testJsonThrowsForEmptyBodyByDefault(): void {

        $response = new Response(204, [], null, [], null, '');

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Response body is empty.');

        $response->json();
    }

    /**
     * 不正なJSONのデコード失敗をInvalidResponseExceptionへ包むことを検証する。
     */
    public function testJsonWrapsDecodeFailure(): void {

        $response = new Response(200, [], '{invalid-json', [], null, '');

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Failed to decode JSON.');

        $response->json();
    }

    /**
     * cURLの生エラーコードをCurlError enumへ変換できることを検証する。
     */
    public function testCurlErrorConvertsRawErrorCodeToEnum(): void {

        $response = new Response(
            statusCode:   0,
            headers:      [],
            body:         null,
            info:         [],
            error:        CURLE_COULDNT_CONNECT,
            errorMessage: 'connection failed',
        );

        self::assertSame(CURLE_COULDNT_CONNECT, $response->error);
        self::assertSame(CurlError::COULDNT_CONNECT, $response->toCurlError());
    }

    /**
     * 未知のcURLエラーコードはOTHERへ変換されることを検証する。
     */
    public function testCurlErrorReturnsOtherForUnknownErrorCode(): void {

        $response = new Response(
            statusCode:   0,
            headers:      [],
            body:         null,
            info:         [],
            error:        99999,
            errorMessage: 'unknown error',
        );

        self::assertSame(CurlError::OTHER, $response->toCurlError());
    }

    /**
     * cURLエラーが無い場合はenum変換もnullになることを検証する。
     */
    public function testCurlErrorReturnsNullWhenRawErrorCodeIsNull(): void {

        $response = new Response(200, [], null, [], null, '');

        self::assertNull($response->toCurlError());
    }
}
