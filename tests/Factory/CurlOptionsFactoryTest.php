<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Factory;

use Ennacx\SimpleCurl\Entity\Config\AuthConfig;
use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\Request;
use Ennacx\SimpleCurl\Entity\RequestAttachment;
use Ennacx\SimpleCurl\Enum\ContentType;
use Ennacx\SimpleCurl\Enum\MediaRange;
use Ennacx\SimpleCurl\Exception\RequestBodyException;
use Ennacx\SimpleCurl\Factory\CurlOptionsFactory;
use PHPUnit\Framework\TestCase;

/**
 * CurlOptionsFactoryがPreparedRequestをcURLオプション配列へ変換する処理を検証する。
 */
final class CurlOptionsFactoryTest extends TestCase {

    /**
     * デフォルト設定のPreparedRequestから基本cURLオプションを生成できることを検証する。
     *
     * @return void
     */
    public function testBuildsDefaultOptionsFromPreparedRequest(): void {

        $preparedRequest = Request::get('https://example.com')
            ->headers(['Accept' => 'text/html'])
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame('https://example.com', $options[CURLOPT_URL]);
        self::assertTrue($options[CURLOPT_RETURNTRANSFER]);
        self::assertTrue($options[CURLOPT_HEADER]);
        self::assertTrue($options[CURLOPT_HTTPGET]);
        self::assertSame(['Accept: text/html'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * URL作成時点のクエリと追加クエリがCURLOPT_URLへ反映されることを検証する。
     *
     * @return void
     */
    public function testBuildsUrlWithQueryParameters(): void {

        $preparedRequest = Request::get('https://example.com/search?b=2&a=1')
            ->param('c', '3')
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);
        $urlParts = self::parseUrlWithQuery($options[CURLOPT_URL]);

        self::assertSame('https', $urlParts['scheme']);
        self::assertSame('example.com', $urlParts['host']);
        self::assertSame('/search', $urlParts['path']);
        self::assertSame([
            'b' => '2',
            'a' => '1',
            'c' => '3',
        ], $urlParts['query']);
    }

    /**
     * フラグメント付きURLでもクエリがフラグメントより前に付与されることを検証する。
     *
     * @return void
     */
    public function testBuildsUrlWithQueryParametersBeforeFragment(): void {

        $preparedRequest = Request::get('https://example.com/path?foo=1#section')
            ->params([
                'bar' => '2',
                'baz' => '3',
            ])
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);
        $urlParts = self::parseUrlWithQuery($options[CURLOPT_URL]);

        self::assertSame('https', $urlParts['scheme']);
        self::assertSame('example.com', $urlParts['host']);
        self::assertSame('/path', $urlParts['path']);
        self::assertSame('section', $urlParts['fragment']);
        self::assertSame([
            'foo' => '1',
            'bar' => '2',
            'baz' => '3',
        ], $urlParts['query']);
    }

    /**
     * ボディとヘッダーの取得が不要な場合にCURLOPT_RETURNTRANSFERが無効になることを検証する。
     *
     * @return void
     */
    public function testDisablesReturnTransferWhenBodyAndHeadersAreNotCaptured(): void {

        $preparedRequest = Request::get('https://example.com')
            ->prepare(
                CurlOptions::create()
                    ->captureBody(false)
                    ->captureHeaders(false)
            );

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertFalse($options[CURLOPT_RETURNTRANSFER]);
        self::assertFalse($options[CURLOPT_HEADER]);
    }

    /**
     * Timeout、Redirect、Bearer認証がcURLオプションと送信ヘッダーへ反映されることを検証する。
     *
     * @return void
     */
    public function testAppliesTimeoutRedirectAndAuthHeaders(): void {

        $preparedRequest = Request::get('https://example.com')
            ->headers(['Accept' => 'application/json'])
            ->prepare(CurlOptions::create(
                AuthConfig::bearer('token'),
                TimeoutConfig::seconds(timeoutSec: 9, connectTimeoutSec: 4),
                RedirectConfig::enabled(maxRedirects: 2, autoReferer: false),
            ));

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame(9, $options[CURLOPT_TIMEOUT]);
        self::assertSame(4, $options[CURLOPT_CONNECTTIMEOUT]);
        self::assertTrue($options[CURLOPT_FOLLOWLOCATION]);
        self::assertSame(2, $options[CURLOPT_MAXREDIRS]);
        self::assertFalse($options[CURLOPT_AUTOREFERER]);
        self::assertSame([
            'Accept: application/json',
            'Authorization: Bearer token',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * User-AgentとRefererが送信ヘッダーへ反映されることを検証する。
     *
     * @return void
     */
    public function testAppliesClientHeaders(): void {

        $preparedRequest = Request::get('https://example.com')
            ->prepare(
                CurlOptions::create()
                    ->userAgent('php-simple-curl-test/1.0')
                    ->referer('https://example.com/from')
            );

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame([
            'User-Agent: php-simple-curl-test/1.0',
            'Referer: https://example.com/from',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * Requestで明示されたUser-AgentとRefererはClientConfigで上書きしないことを検証する。
     *
     * @return void
     */
    public function testKeepsUserDefinedClientHeaders(): void {

        $preparedRequest = Request::get('https://example.com')
            ->headers([
                'User-Agent' => 'custom-agent/2.0',
                'Referer'    => 'https://example.com/custom',
            ])
            ->prepare(
                CurlOptions::create()
                    ->userAgent('php-simple-curl-test/1.0')
                    ->referer('https://example.com/from')
            );

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame([
            'User-Agent: custom-agent/2.0',
            'Referer: https://example.com/custom',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * Requestのaccept()で指定したメディアタイプがAcceptヘッダーへ反映されることを検証する。
     *
     * @return void
     */
    public function testBuildsAcceptHeaderFromRequestAcceptTypes(): void {

        $preparedRequest = Request::get('https://example.com')
            ->accepts(ContentType::Json, 'application/vnd.api+json')
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame([
            'Accept: application/json, application/vnd.api+json',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * Quality Value付きAccept値がAcceptヘッダーへ反映されることを検証する。
     *
     * @return void
     */
    public function testBuildsAcceptHeaderWithQualityValues(): void {

        $preparedRequest = Request::get('https://example.com')
            ->accepts(
                ContentType::Json->withQuality(1.0),
                ContentType::Html->withQuality(0.8),
                MediaRange::Any->withQuality(0.1),
            )
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame([
            'Accept: application/json;q=1, text/html;q=0.8, */*;q=0.1',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * Requestで明示されたAcceptヘッダーはaccept()で上書きしないことを検証する。
     *
     * @return void
     */
    public function testKeepsUserDefinedAcceptHeader(): void {

        $preparedRequest = Request::get('https://example.com')
            ->headers(['Accept' => 'application/problem+json'])
            ->accept(ContentType::Json)
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame([
            'Accept: application/problem+json',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * プレーンテキストのリクエストボディがcURLオプションへ反映されることを検証する。
     *
     * @return void
     */
    public function testBuildsPlainTextRequestBodyOptions(): void {

        $preparedRequest = Request::post('https://example.com/messages')
            ->body('plain text message', ContentType::PlainText)
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame('plain text message', $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: text/plain'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * ファイル内容をリクエストボディとしてcURLオプションへ反映できることを検証する。
     *
     * @return void
     */
    public function testBuildsRequestBodyOptionsFromFile(): void {

        // テンポラリに一時ファイルを生成
        $path = tempnam(sys_get_temp_dir(), 'simple-curl-body-');
        self::assertIsString($path);
        file_put_contents($path, "line 1\nline 2\n");

        try{
            $preparedRequest = Request::post('https://example.com/upload')
                ->bodyFromFile($path, ContentType::PlainText)
                ->prepare();

            $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

            self::assertSame("line 1\nline 2\n", $options[CURLOPT_POSTFIELDS]);
            self::assertSame(['Content-Type: text/plain'], $options[CURLOPT_HTTPHEADER]);
        } finally{
            unlink($path);
        }
    }

    /**
     * `0` のようなempty判定される文字列でもリクエストボディとして扱えることを検証する。
     *
     * @return void
     */
    public function testBuildsZeroStringRequestBodyOptions(): void {

        $preparedRequest = Request::post('https://example.com/messages')
            ->body('0')
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame('0', $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: text/plain'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * JSONリクエストボディがJSON文字列とContent-Typeへ変換されることを検証する。
     *
     * @return void
     */
    public function testBuildsJsonRequestBodyOptions(): void {

        $preparedRequest = Request::post('https://example.com/users')
            ->json([
                'name' => 'Taro',
                'tags' => ['php', 'curl'],
            ])
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame('{"name":"Taro","tags":["php","curl"]}', $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: application/json'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * JSON文字列をそのままJSONリクエストボディとして扱えることを検証する。
     *
     * @return void
     */
    public function testBuildsJsonRequestBodyOptionsFromJsonString(): void {

        $json = '{"name":"Taro","tags":["php","curl"]}';
        $preparedRequest = Request::post('https://example.com/users')
            ->json($json)
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame($json, $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: application/json'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * フォームリクエストボディがURLエンコード済み文字列とContent-Typeへ変換されることを検証する。
     *
     * @return void
     */
    public function testBuildsFormRequestBodyOptions(): void {

        $preparedRequest = Request::post('https://example.com/token')
            ->form([
                'grant_type' => 'client_credentials',
                'client_id'  => 'example-client',
            ])
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame('grant_type=client_credentials&client_id=example-client', $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: application/x-www-form-urlencoded'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * ユーザーが指定したContent-Typeはリクエストボディの既定値で上書きしないことを検証する。
     *
     * @return void
     */
    public function testKeepsUserDefinedContentTypeForRequestBody(): void {

        $preparedRequest = Request::post('https://example.com/users')
            ->headers(['Content-Type' => 'application/vnd.api+json'])
            ->json(['name' => 'Taro'])
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame('{"name":"Taro"}', $options[CURLOPT_POSTFIELDS]);
        self::assertSame(['Content-Type: application/vnd.api+json'], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * `X-Content-Type-Options` はContent-Type指定とは扱わないことを検証する。
     *
     * @return void
     */
    public function testAddsDefaultContentTypeWhenOnlyContentTypeOptionsHeaderExists(): void {

        $preparedRequest = Request::post('https://example.com/users')
            ->headers(['X-Content-Type-Options' => 'nosniff'])
            ->json(['name' => 'Taro'])
            ->prepare();

        $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

        self::assertSame('{"name":"Taro"}', $options[CURLOPT_POSTFIELDS]);
        self::assertSame([
            'X-Content-Type-Options: nosniff',
            'Content-Type: application/json',
        ], $options[CURLOPT_HTTPHEADER]);
    }

    /**
     * 添付ファイルをmultipart/form-data用のPOSTFIELDSへ変換できることを検証する。
     *
     * @return void
     */
    public function testBuildsMultipartPostFieldsFromAttachment(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $preparedRequest = Request::post('https://example.com/upload')
                ->attach(new RequestAttachment('file', $path, 'sample.txt', 'text/plain'))
                ->prepare();

            $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

            self::assertArrayHasKey(CURLOPT_POSTFIELDS, $options);
            self::assertIsArray($options[CURLOPT_POSTFIELDS]);
            self::assertArrayHasKey('file', $options[CURLOPT_POSTFIELDS]);
            self::assertInstanceOf(\CURLFile::class, $options[CURLOPT_POSTFIELDS]['file']);
            self::assertArrayNotHasKey(CURLOPT_HTTPHEADER, $options);
        } finally{
            unlink($path);
        }
    }

    /**
     * 添付ファイルがある場合、ユーザー指定のContent-Typeを削除してcURLにboundary生成を任せることを検証する。
     *
     * @return void
     */
    public function testRemovesUserDefinedContentTypeForMultipartRequest(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $preparedRequest = Request::post('https://example.com/upload')
                ->headers([
                    'Content-Type' => 'multipart/form-data',
                    'Accept'       => 'application/json',
                ])
                ->attach(new RequestAttachment('file', $path))
                ->prepare();

            $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

            self::assertSame(['Accept: application/json'], $options[CURLOPT_HTTPHEADER]);
        } finally{
            unlink($path);
        }
    }

    /**
     * フォーム項目と添付ファイルをmultipart/form-data用のPOSTFIELDSへ統合できることを検証する。
     *
     * @return void
     */
    public function testBuildsMultipartPostFieldsWithFormFields(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $preparedRequest = Request::post('https://example.com/upload')
                ->form(['name' => 'Taro'])
                ->attach(new RequestAttachment('file', $path))
                ->prepare();

            $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

            self::assertIsArray($options[CURLOPT_POSTFIELDS]);
            self::assertSame('Taro', $options[CURLOPT_POSTFIELDS]['name']);
            self::assertInstanceOf(\CURLFile::class, $options[CURLOPT_POSTFIELDS]['file']);
        } finally{
            unlink($path);
        }
    }

    /**
     * 添付ファイル設定後に追加したフォーム項目もmultipart/form-data用のPOSTFIELDSへ統合できることを検証する。
     *
     * @return void
     */
    public function testBuildsMultipartPostFieldsWhenFormIsAddedAfterAttachment(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $preparedRequest = Request::post('https://example.com/upload')
                ->attach(new RequestAttachment('file', $path))
                ->form(['name' => 'Taro'])
                ->prepare();

            $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

            self::assertIsArray($options[CURLOPT_POSTFIELDS]);
            self::assertSame('Taro', $options[CURLOPT_POSTFIELDS]['name']);
            self::assertInstanceOf(\CURLFile::class, $options[CURLOPT_POSTFIELDS]['file']);
        } finally{
            unlink($path);
        }
    }

    /**
     * 添付ファイル名とフォーム項目名が重複した場合は、添付ファイル側のoverwrite設定で上書きできることを検証する。
     *
     * @return void
     */
    public function testCanOverwriteFormFieldWhenAttachmentNameDuplicates(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $preparedRequest = Request::post('https://example.com/upload')
                ->form(['file' => 'replace me'])
                ->attach(new RequestAttachment('file', $path), allowOverwrite: true)
                ->prepare();

            $options = (new CurlOptionsFactory())->fromPreparedRequest($preparedRequest);

            self::assertInstanceOf(\CURLFile::class, $options[CURLOPT_POSTFIELDS]['file']);
        } finally{
            unlink($path);
        }
    }

    /**
     * 添付ファイルとJSONボディは同時に送信できないことを検証する。
     *
     * @return void
     */
    public function testMultipartRejectsJsonBody(): void {

        $path = tempnam(sys_get_temp_dir(), 'simple-curl-attach-');
        self::assertIsString($path);
        file_put_contents($path, 'attachment body');

        try{
            $request = Request::post('https://example.com/upload')
                ->json(['name' => 'Taro']);

            $this->expectException(RequestBodyException::class);

            $request->attach(new RequestAttachment('file', $path));
        } finally{
            unlink($path);
        }
    }

    /**
     * URLをパースし、クエリ文字列を連想配列へ正規化する。
     *
     * @param  string $url
     * @return array{scheme: string, host: string, path: string, query: array<string, mixed>, fragment?: string}
     */
    private static function parseUrlWithQuery(string $url): array {

        $parts = parse_url($url);
        self::assertIsArray($parts);

        $query = [];
        if(isset($parts['query'])){
            parse_str($parts['query'], $query);
        }

        return [
            'scheme'   => $parts['scheme'] ?? '',
            'host'     => $parts['host'] ?? '',
            'path'     => $parts['path'] ?? '',
            'query'    => $query,
            'fragment' => $parts['fragment'] ?? null,
        ];
    }
}
