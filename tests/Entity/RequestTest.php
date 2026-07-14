<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Entity;

use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\PreparedRequest;
use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Entity\RequestAttachment;
use Ennacx\SimpleCurl\Entity\QualifiedAcceptValue;
use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Enum\ContentType;
use Ennacx\SimpleCurl\Enum\MediaRange;
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
     * accept()でAcceptヘッダー用のメディアタイプを追加でき、元のRequestを変更しないことを検証する。
     *
     * @return void
     */
    public function testAcceptAddsAcceptedContentTypeAndKeepsOriginalRequest(): void {

        $request = Request::get('https://example.com');
        $updated = $request
            ->accept(ContentType::Json)
            ->accept('application/vnd.api+json');

        self::assertSame([], $request->acceptHeaders);
        self::assertSame([
            'application/json',
            'application/vnd.api+json',
        ], $updated->acceptHeaders);
    }

    /**
     * accepts()で複数のメディアタイプを追加でき、重複した値は追加しないことを検証する。
     *
     * @return void
     */
    public function testAcceptsAddsMultipleTypesAndSkipsDuplicates(): void {

        $request = Request::get('https://example.com')
            ->accepts(
                ContentType::Json,
                'text/html',
                ContentType::Json,
                ' text/html '
            );

        self::assertSame([
            'application/json',
            'text/html',
        ], $request->acceptHeaders);
    }

    /**
     * accept()でMediaRangeやQuality Value付きAccept値を追加できることを検証する。
     *
     * @return void
     */
    public function testAcceptAddsMediaRangeAndQualifiedValue(): void {

        $request = Request::get('https://example.com')
            ->accept(MediaRange::Any)
            ->accept(ContentType::Json->withQuality(0.9))
            ->accept(new QualifiedAcceptValue('application/vnd.api+json', 0.75));

        self::assertSame([
            '*/*',
            'application/json;q=0.9',
            'application/vnd.api+json;q=0.75',
        ], $request->acceptHeaders);
    }

    /**
     * Quality ValueがAcceptヘッダー用の文字列へ整形されることを検証する。
     *
     * @return void
     */
    public function testAcceptFormatsQualityValue(): void {

        $request = Request::get('https://example.com')
            ->accept(ContentType::Json->withQuality(0.8))
            ->accept(MediaRange::Any->withQuality(0.1234));

        self::assertSame([
            'application/json;q=0.8',
            '*/*;q=0.123',
        ], $request->acceptHeaders);
    }

    /**
     * Quality Valueに0を明示できることを検証する。
     *
     * @return void
     */
    public function testAcceptAllowsZeroQualityValue(): void {

        $request = Request::get('https://example.com')
            ->accept(MediaRange::Any->withQuality(0.0));

        self::assertSame(['*/*;q=0'], $request->acceptHeaders);
    }

    /**
     * 同じメディアタイプを異なるQuality Valueで追加した場合、先に追加した値を維持することを検証する。
     *
     * @return void
     */
    public function testAcceptSkipsDuplicateMediaRangeWithDifferentQualityValue(): void {

        $request = Request::get('https://example.com')
            ->accepts(
                ContentType::Json,
                ContentType::Json->withQuality(0.5),
                'application/json;q=0.8'
            );

        self::assertSame(['application/json'], $request->acceptHeaders);
    }

    /**
     * Quality Valueが1を超える場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testAcceptThrowsExceptionForInvalidQualityValue(): void {

        $this->expectException(InvalidArgumentException::class);

        ContentType::Json->withQuality(1.1);
    }

    /**
     * Quality Valueが0未満の場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testAcceptThrowsExceptionForNegativeQualityValue(): void {

        $this->expectException(InvalidArgumentException::class);

        ContentType::Json->withQuality(-0.1);
    }

    /**
     * すでにQuality Value付きのAccept値を再度ラップした場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testAcceptThrowsExceptionForQualifiedAcceptValueWrapping(): void {

        $this->expectException(InvalidArgumentException::class);

        new QualifiedAcceptValue(ContentType::Json->withQuality(0.8), 0.5);
    }

    /**
     * q値を含むAccept文字列を再度ラップした場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testAcceptThrowsExceptionForQualifiedStringWrapping(): void {

        $this->expectException(InvalidArgumentException::class);

        new QualifiedAcceptValue('application/json;q=0.8', 0.5);
    }

    /**
     * 空のメディアタイプをAcceptヘッダーに追加しようとした場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testAcceptThrowsExceptionForEmptyType(): void {

        $this->expectException(InvalidArgumentException::class);

        Request::get('https://example.com')
            ->accept('   ');
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
            $updated = $request->bodyFromFile($path, ContentType::PlainText);

            self::assertNull($request->requestBody);
            self::assertNull($request->contentType);
            self::assertNotNull($updated->requestBody);
            self::assertSame("file body\n", $updated->requestBody->body);
            self::assertSame(ContentType::PlainText, $updated->requestBody->contentType);
            self::assertSame(ContentType::PlainText, $updated->contentType);
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
     * attach()で添付ファイルを追加でき、元のRequestを変更しないことを検証する。
     *
     * @return void
     */
    public function testAttachAddsAttachmentAndKeepsOriginalRequest(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $attachment = new RequestAttachment('file', $path, 'sample.txt', 'text/plain');
            $request = Request::post('https://example.com/upload');
            $updated = $request->attach($attachment);

            self::assertSame([], $request->attachmentEntries);
            self::assertNull($request->contentType);
            self::assertCount(1, $updated->attachmentEntries);
            self::assertSame($attachment, $updated->attachmentEntries[0]->attachment);
            self::assertTrue($updated->attachmentEntries[0]->allowOverwrite);
            self::assertNull($updated->contentType);
        } finally{
            unlink($path);
        }
    }

    /**
     * attachFile()で簡易的に添付ファイルを追加できることを検証する。
     *
     * @return void
     */
    public function testAttachFileAddsAttachmentFromPath(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $request = Request::post('https://example.com/upload')
                ->attachFile('file', $path, allowOverwrite: false);

            self::assertCount(1, $request->attachmentEntries);
            self::assertSame('file', $request->attachmentEntries[0]->attachment->name);
            self::assertSame($path, $request->attachmentEntries[0]->attachment->path);
            self::assertFalse($request->attachmentEntries[0]->allowOverwrite);
        } finally{
            unlink($path);
        }
    }

    /**
     * 存在しないファイルを添付しようとした場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testAttachThrowsExceptionForMissingFile(): void {

        $this->expectException(InvalidArgumentException::class);

        Request::post('https://example.com/upload')
            ->attach(new RequestAttachment('file', sys_get_temp_dir() . '/simple-curl-missing-attachment'));
    }

    /**
     * 空のフィールド名で添付しようとした場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testAttachThrowsExceptionForEmptyName(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $this->expectException(InvalidArgumentException::class);

            Request::post('https://example.com/upload')
                ->attach(new RequestAttachment('   ', $path));
        } finally{
            unlink($path);
        }
    }

    /**
     * overwrite=falseで同名添付ファイルを追加しようとした場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testAttachThrowsExceptionForDuplicateAttachmentNameWhenOverwriteDisabled(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $this->expectException(InvalidArgumentException::class);

            Request::post('https://example.com/upload')
                ->attach(new RequestAttachment('file', $path), allowOverwrite: false)
                ->attach(new RequestAttachment('file', $path), allowOverwrite: false);
        } finally{
            unlink($path);
        }
    }

    /**
     * overwrite=falseでフォーム項目と同名の添付ファイルを追加しようとした場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testAttachThrowsExceptionForDuplicateFormFieldWhenOverwriteDisabled(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $this->expectException(InvalidArgumentException::class);

            Request::post('https://example.com/upload')
                ->form(['file' => 'keep me'])
                ->attach(new RequestAttachment('file', $path), allowOverwrite: false);
        } finally{
            unlink($path);
        }
    }

    /**
     * 添付ファイル設定後に通常ボディを設定しようとした場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testBodyThrowsExceptionWhenAttachmentAlreadySet(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $this->expectException(InvalidArgumentException::class);

            Request::post('https://example.com/upload')
                ->attach(new RequestAttachment('file', $path))
                ->body('plain text');
        } finally{
            unlink($path);
        }
    }

    /**
     * 添付ファイル設定後にJSONボディを設定しようとした場合に例外を投げることを検証する。
     *
     * @return void
     */
    public function testJsonThrowsExceptionWhenAttachmentAlreadySet(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $this->expectException(InvalidArgumentException::class);

            Request::post('https://example.com/upload')
                ->attach(new RequestAttachment('file', $path))
                ->json(['name' => 'Taro']);
        } finally{
            unlink($path);
        }
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
        self::assertNull($updated->contentType);
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
