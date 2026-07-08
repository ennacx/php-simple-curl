<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Integration;

use Ennacx\SimpleCurl\Client\MultiClient;
use Ennacx\SimpleCurl\Entity\CurlOptions;
use Ennacx\SimpleCurl\Entity\Request;

/**
 * MultiClientが複数のローカルHTTPリクエストを並列実行できることを検証する。
 */
final class MultiClientIntegrationTest extends LocalHttpServerTestCase {

    /**
     * 複数レスポンスが各Request IDをキーにして返却されることを検証する。
     *
     * @return void
     */
    public function testSendAllReturnsResponsesKeyedByRequestId(): void {

        $options = CurlOptions::create()->timeout(5)->followRedirects();

        $json = Request::get(self::url('/json'))
            ->headers(['Accept' => 'application/json'])
            ->withOptions($options);
        $text = Request::get(self::url('/text'))
            ->withOptions($options);
        $missing = Request::get(self::url('/status/404'))
            ->withOptions($options);

        $responses = (new MultiClient())->sendAll($json, $text, $missing);

        self::assertCount(3, $responses);
        self::assertArrayHasKey($json->request->id, $responses);
        self::assertArrayHasKey($text->request->id, $responses);
        self::assertArrayHasKey($missing->request->id, $responses);

        self::assertTrue($responses[$json->request->id]->isSuccessful());
        self::assertSame('GET', $responses[$json->request->id]->json()['method']);

        self::assertTrue($responses[$text->request->id]->isSuccessful());
        self::assertSame('hello from fixture', $responses[$text->request->id]->body);

        self::assertTrue($responses[$missing->request->id]->isClientError());
        self::assertTrue($responses[$missing->request->id]->isError());
    }
}
