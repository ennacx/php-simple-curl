<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Integration;

use Ennacx\SimpleCurl\Client\MultiClient;
use Ennacx\SimpleCurl\Option\CurlOptions;
use Ennacx\SimpleCurl\Request\Request;

/**
 * MultiClientが複数のローカルHTTPリクエストを並列実行できることを検証する。
 */
final class MultiClientIntegrationTest extends LocalHttpServerTestCase {

    /**
     * 複数レスポンスが各Request IDをキーにして返却されることを検証する。
     */
    public function testSendAllReturnsResponsesKeyedByRequestId(): void {

        $options = CurlOptions::create()->timeout(5)->followRedirects();

        $json = Request::get(self::url('/json'))
            ->headers(['Accept' => 'application/json'])
            ->prepare($options);
        $text = Request::get(self::url('/text'))
            ->prepare($options);
        $missing = Request::get(self::url('/status/404'))
            ->prepare($options);

        $responses = (new MultiClient())->sendAll($json, $text, $missing);

        self::assertCount(3, $responses);
        self::assertArrayHasKey($json->getRequest()->getId(), $responses);
        self::assertArrayHasKey($text->getRequest()->getId(), $responses);
        self::assertArrayHasKey($missing->getRequest()->getId(), $responses);

        self::assertTrue($responses[$json->getRequest()->getId()]->isSuccessful());
        self::assertSame('GET', $responses[$json->getRequest()->getId()]->json()['method']);

        self::assertTrue($responses[$text->getRequest()->getId()]->isSuccessful());
        self::assertSame('hello from fixture', $responses[$text->getRequest()->getId()]->body);

        self::assertTrue($responses[$missing->getRequest()->getId()]->isClientError());
        self::assertTrue($responses[$missing->getRequest()->getId()]->isError());
    }

    /**
     * Requestを直接渡した場合に、各Requestが内部でPreparedRequestへ変換されて並列実行できることを検証する。
     */
    public function testSendAllAcceptsRequestsAndPreparesThemInternally(): void {

        $json = Request::get(self::url('/json'))
            ->headers(['Accept' => 'application/json']);
        $text = Request::get(self::url('/text'));

        $responses = (new MultiClient())->sendAll($json, $text);

        self::assertCount(2, $responses);
        self::assertArrayHasKey($json->getId(), $responses);
        self::assertArrayHasKey($text->getId(), $responses);

        self::assertTrue($responses[$json->getId()]->isSuccessful());
        self::assertSame('GET', $responses[$json->getId()]->json()['method']);
        self::assertSame('application/json', $responses[$json->getId()]->json()['accept']);

        self::assertTrue($responses[$text->getId()]->isSuccessful());
        self::assertSame('hello from fixture', $responses[$text->getId()]->body);
    }
}
