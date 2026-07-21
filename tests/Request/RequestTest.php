<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Request;

use Ennacx\SimpleCurl\Enum\ContentType;
use Ennacx\SimpleCurl\Enum\CurlMethod;
use Ennacx\SimpleCurl\Enum\MediaRange;
use Ennacx\SimpleCurl\Exception\InvalidRequestException;
use Ennacx\SimpleCurl\Exception\RequestBodyException;
use Ennacx\SimpleCurl\Option\CurlOptions;
use Ennacx\SimpleCurl\Request\PreparedRequest;
use Ennacx\SimpleCurl\Request\QualifiedAcceptValue;
use Ennacx\SimpleCurl\Request\Request;
use Ennacx\SimpleCurl\Request\RequestAttachment;
use PHPUnit\Framework\TestCase;

/**
 * Requestの生成、入力検証、PreparedRequest化を検証する。
 */
final class RequestTest extends TestCase {

    /**
     * HTTPメソッド名の静的FactoryからRequestを生成できることを検証する。
     */
    public function testStaticFactoryCreatesRequestWithHttpMethod(): void {

        $request = Request::post('https://example.com');

        self::assertSame('https://example.com', $request->getUrl());
        self::assertSame(CurlMethod::POST, $request->getMethod());
        self::assertNotSame('', $request->getId());
    }

    /**
     * 送信ヘッダーの値が文字列へ正規化されることを検証する。
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
        ], $request->getHeaders());
    }

    /**
     * accept()でAcceptヘッダー用のメディアタイプを追加でき、元のRequestを変更しないことを検証する。
     */
    public function testAcceptAddsAcceptedContentTypeAndKeepsOriginalRequest(): void {

        $request = Request::get('https://example.com');
        $updated = $request
            ->accept(ContentType::Json)
            ->accept('application/vnd.api+json');

        self::assertSame([], $request->getAcceptHeaders());
        self::assertSame([
            'application/json',
            'application/vnd.api+json',
        ], $updated->getAcceptHeaders());
    }

    /**
     * accepts()で複数のメディアタイプを追加でき、重複した値は追加しないことを検証する。
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
        ], $request->getAcceptHeaders());
    }

    /**
     * accept()でMediaRangeやQuality Value付きAccept値を追加できることを検証する。
     */
    public function testAcceptAddsMediaRangeAndQualifiedValue(): void {

        $request = Request::get('https://example.com')
            ->accept(MediaRange::Any)
            ->accept(ContentType::Json->withQuality(0.9))
            ->accept(QualifiedAcceptValue::create('application/vnd.api+json', 0.75));

        self::assertSame([
            '*/*',
            'application/json;q=0.9',
            'application/vnd.api+json;q=0.75',
        ], $request->getAcceptHeaders());
    }

    /**
     * Quality ValueがAcceptヘッダー用の文字列へ整形されることを検証する。
     */
    public function testAcceptFormatsQualityValue(): void {

        $request = Request::get('https://example.com')
            ->accept(ContentType::Json->withQuality(0.8))
            ->accept(MediaRange::Any->withQuality(0.1234));

        self::assertSame([
            'application/json;q=0.8',
            '*/*;q=0.123',
        ], $request->getAcceptHeaders());
    }

    /**
     * Quality Valueに0を明示できることを検証する。
     */
    public function testAcceptAllowsZeroQualityValue(): void {

        $request = Request::get('https://example.com')
            ->accept(MediaRange::Any->withQuality(0.0));

        self::assertSame(['*/*;q=0'], $request->getAcceptHeaders());
    }

    /**
     * 同じメディアタイプを異なるQuality Valueで追加した場合、先に追加した値を維持することを検証する。
     */
    public function testAcceptSkipsDuplicateMediaRangeWithDifferentQualityValue(): void {

        $request = Request::get('https://example.com')
            ->accepts(
                ContentType::Json,
                ContentType::Json->withQuality(0.5),
                'application/json;q=0.8'
            );

        self::assertSame(['application/json'], $request->getAcceptHeaders());
    }

    /**
     * Quality Valueが1を超える場合に例外を投げることを検証する。
     */
    public function testAcceptThrowsExceptionForInvalidQualityValue(): void {

        $this->expectException(InvalidRequestException::class);

        ContentType::Json->withQuality(1.1);
    }

    /**
     * Quality Valueが0未満の場合に例外を投げることを検証する。
     */
    public function testAcceptThrowsExceptionForNegativeQualityValue(): void {

        $this->expectException(InvalidRequestException::class);

        ContentType::Json->withQuality(-0.1);
    }

    /**
     * すでにQuality Value付きのAccept値を再度ラップした場合に例外を投げることを検証する。
     */
    public function testAcceptThrowsExceptionForQualifiedAcceptValueWrapping(): void {

        $this->expectException(InvalidRequestException::class);

        QualifiedAcceptValue::create(ContentType::Json->withQuality(0.8), 0.5);
    }

    /**
     * q値を含むAccept文字列を再度ラップした場合に例外を投げることを検証する。
     */
    public function testAcceptThrowsExceptionForQualifiedStringWrapping(): void {

        $this->expectException(InvalidRequestException::class);

        QualifiedAcceptValue::create('application/json;q=0.8', 0.5);
    }

    /**
     * 空のメディアタイプをAcceptヘッダーに追加しようとした場合に例外を投げることを検証する。
     */
    public function testAcceptThrowsExceptionForEmptyType(): void {

        $this->expectException(InvalidRequestException::class);

        Request::get('https://example.com')
            ->accept('   ');
    }

    /**
     * URLに含まれる既存クエリがRequest生成時にqueryParamsへ分離されることを検証する。
     */
    public function testExistingQueryStringIsParsedOnCreate(): void {

        $request = Request::get('https://example.com/search?b=2&a=1');

        self::assertSame('https://example.com/search', $request->getUrl());
        self::assertSame([
            'b' => '2',
            'a' => '1',
        ], $request->getQueryParams());
    }

    /**
     * param()でクエリの追加・上書き・削除ができ、元のRequestは変更されないことを検証する。
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
        ], $request->getQueryParams());
        self::assertSame([
            'keep'  => '9',
            'added' => '3',
        ], $updated->getQueryParams());
    }

    /**
     * params()で複数クエリを追加でき、overwrite=falseでは既存値を維持することを検証する。
     */
    public function testParamsAddsQueryParametersAndCanKeepExistingValues(): void {

        $request = Request::get('https://example.com?keep=1');
        $updated = $request->params([
            'keep' => '2',
            'new' => '3',
            0 => 'ignored',
        ], overwrite: false);

        self::assertSame(['keep' => '1'], $request->getQueryParams());
        self::assertSame([
            'keep' => '1',
            'new'  => '3',
        ], $updated->getQueryParams());
    }

    /**
     * スキームのないURLを不正として扱うことを検証する。
     */
    public function testInvalidUrlThrowsException(): void {

        $this->expectException(InvalidRequestException::class);

        Request::get('example.com');
    }

    /**
     * 空のヘッダー名を不正として扱うことを検証する。
     */
    public function testInvalidHeaderNameThrowsException(): void {

        $this->expectException(InvalidRequestException::class);

        Request::get('https://example.com')->headers(['' => 'value']);
    }

    /**
     * ファイル内容をリクエストボディーとして設定でき、元のRequestを変更しないことを検証する。
     */
    public function testBodyFromFileCreatesRequestBodyFromReadableFile(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-request-');
        self::assertIsString($path);

        file_put_contents($path, "file body\n");

        try{
            $request = Request::post('https://example.com/upload');
            $updated = $request->bodyFromFile($path, ContentType::PlainText);

            self::assertNull($request->getRequestBody());
            self::assertNull($request->getContentType());
            self::assertNotNull($updated->getRequestBody());
            self::assertSame("file body\n", $updated->getRequestBody()->body);
            self::assertSame(ContentType::PlainText, $updated->getRequestBody()->contentType);
            self::assertSame(ContentType::PlainText, $updated->getContentType());
        } finally{
            unlink($path);
        }
    }

    /**
     * 存在しないファイルをリクエストボディーに指定した場合に例外を投げることを検証する。
     */
    public function testBodyFromFileThrowsExceptionForMissingFile(): void {

        $this->expectException(RequestBodyException::class);

        Request::post('https://example.com/upload')
            ->bodyFromFile(sys_get_temp_dir() . '/simple-curl-missing-file');
    }

    /**
     * attach()で添付ファイルを追加でき、元のRequestを変更しないことを検証する。
     */
    public function testAttachAddsAttachmentAndKeepsOriginalRequest(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $attachment = new RequestAttachment('file', $path, 'sample.txt', 'text/plain');
            $request = Request::post('https://example.com/upload');
            $updated = $request->attach($attachment);

            self::assertSame([], $request->getAttachmentEntries());
            self::assertNull($request->getContentType());
            self::assertCount(1, $updated->getAttachmentEntries());
            self::assertSame($attachment, $updated->getAttachmentEntries()[0]->attachment);
            self::assertTrue($updated->getAttachmentEntries()[0]->allowOverwrite);
            self::assertNull($updated->getContentType());
        } finally{
            unlink($path);
        }
    }

    /**
     * attachFile()で簡易的に添付ファイルを追加できることを検証する。
     */
    public function testAttachFileAddsAttachmentFromPath(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $request = Request::post('https://example.com/upload')
                ->attachFile($path, name: 'file', allowOverwrite: false);

            self::assertCount(1, $request->getAttachmentEntries());
            self::assertSame('file', $request->getAttachmentEntries()[0]->attachment->name);
            self::assertSame($path, $request->getAttachmentEntries()[0]->attachment->path);
            self::assertFalse($request->getAttachmentEntries()[0]->allowOverwrite);
        } finally{
            unlink($path);
        }
    }

    /**
     * attachFile()でnameを省略した場合はファイル名からフィールド名を補完することを検証する。
     */
    public function testAttachFileUsesFilenameAsDefaultFieldName(): void {

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'simple-curl-avatar.txt';
        file_put_contents($path, 'attachment body');

        try{
            $request = Request::post('https://example.com/upload')
                ->attachFile($path);

            self::assertCount(1, $request->getAttachmentEntries());
            self::assertSame('simple-curl-avatar', $request->getAttachmentEntries()[0]->attachment->name);
            self::assertSame($path, $request->getAttachmentEntries()[0]->attachment->path);
            self::assertTrue($request->getAttachmentEntries()[0]->allowOverwrite);
        } finally{
            unlink($path);
        }
    }

    /**
     * 存在しないファイルを添付しようとした場合に例外を投げることを検証する。
     */
    public function testAttachThrowsExceptionForMissingFile(): void {

        $this->expectException(RequestBodyException::class);

        Request::post('https://example.com/upload')
            ->attach(new RequestAttachment('file', sys_get_temp_dir() . '/simple-curl-missing-attachment'));
    }

    /**
     * 空のフィールド名で添付しようとした場合に例外を投げることを検証する。
     */
    public function testAttachThrowsExceptionForEmptyName(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $this->expectException(RequestBodyException::class);

            Request::post('https://example.com/upload')
                ->attach(new RequestAttachment('   ', $path));
        } finally{
            unlink($path);
        }
    }

    /**
     * overwrite=falseで同名添付ファイルを追加しようとした場合に例外を投げることを検証する。
     */
    public function testAttachThrowsExceptionForDuplicateAttachmentNameWhenOverwriteDisabled(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $this->expectException(RequestBodyException::class);

            Request::post('https://example.com/upload')
                ->attach(new RequestAttachment('file', $path), allowOverwrite: false)
                ->attach(new RequestAttachment('file', $path), allowOverwrite: false);
        } finally{
            unlink($path);
        }
    }

    /**
     * overwrite=falseでフォーム項目と同名の添付ファイルを追加しようとした場合に例外を投げることを検証する。
     */
    public function testAttachThrowsExceptionForDuplicateFormFieldWhenOverwriteDisabled(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $this->expectException(RequestBodyException::class);

            Request::post('https://example.com/upload')
                ->form(['file' => 'keep me'])
                ->attach(new RequestAttachment('file', $path), allowOverwrite: false);
        } finally{
            unlink($path);
        }
    }

    /**
     * 添付ファイル設定後に通常ボディを設定しようとした場合に例外を投げることを検証する。
     */
    public function testBodyThrowsExceptionWhenAttachmentAlreadySet(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $this->expectException(RequestBodyException::class);

            Request::post('https://example.com/upload')
                ->attach(new RequestAttachment('file', $path))
                ->body('plain text');
        } finally{
            unlink($path);
        }
    }

    /**
     * 添付ファイル設定後にJSONボディを設定しようとした場合に例外を投げることを検証する。
     */
    public function testJsonThrowsExceptionWhenAttachmentAlreadySet(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $this->expectException(RequestBodyException::class);

            Request::post('https://example.com/upload')
                ->attach(new RequestAttachment('file', $path))
                ->json(['name' => 'Taro']);
        } finally{
            unlink($path);
        }
    }

    /**
     * 不正なJSON文字列はthrow=trueの場合に例外を投げることを検証する。
     */
    public function testJsonThrowsExceptionForInvalidJsonString(): void {

        $this->expectException(RequestBodyException::class);

        Request::post('https://example.com/users')
            ->json('{"name":');
    }

    /**
     * 不正なJSON文字列でもthrow=falseの場合は元のRequestを返すことを検証する。
     */
    public function testJsonReturnsOriginalRequestForInvalidJsonStringWhenThrowDisabled(): void {

        $request = Request::post('https://example.com/users');
        $updated = $request->json('{"name":', throw: false);

        self::assertSame($request, $updated);
        self::assertNull($updated->getRequestBody());
        self::assertNull($updated->getContentType());
    }

    /**
     * CurlOptions付きのPreparedRequestを生成できることを検証する。
     */
    public function testPrepareCreatesPreparedRequestWithOptions(): void {

        $request = Request::get('https://example.com');
        $options = CurlOptions::create()->timeout(10);
        $preparedRequest = $request->prepare($options);

        self::assertInstanceOf(PreparedRequest::class, $preparedRequest);
        self::assertSame($request, $preparedRequest->getRequest());
        self::assertSame($options, $preparedRequest->getOptions());
    }

    /**
     * CurlOptionsなしのPreparedRequestを生成できることを検証する。
     */
    public function testPrepareCreatesPreparedRequestWithDefaultOptions(): void {

        $request = Request::get('https://example.com');
        $preparedRequest = $request->prepare();

        self::assertSame($request, $preparedRequest->getRequest());
        self::assertInstanceOf(CurlOptions::class, $preparedRequest->getOptions());
    }
}
