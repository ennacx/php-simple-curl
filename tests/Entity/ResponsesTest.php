<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Entity;

use Ennacx\SimpleCurl\Entity\Response;
use Ennacx\SimpleCurl\Entity\Responses;
use Ennacx\SimpleCurl\Exception\ImmutableCollectionException;
use Ennacx\SimpleCurl\Exception\InvalidResponseException;
use Ennacx\SimpleCurl\Exception\ResponseNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Responses collection behavior.
 */
final class ResponsesTest extends TestCase {

    /**
     * get(), find(), has(), all(), and count() expose keyed responses.
     */
    public function testReadsResponsesByRequestId(): void {

        $ok = self::response(200);
        $notFound = self::response(404);
        $responses = new Responses([
            'request-a' => $ok,
            'request-b' => $notFound,
        ]);

        self::assertSame($ok, $responses->get('request-a'));
        self::assertSame($notFound, $responses->find('request-b'));
        self::assertNull($responses->find('missing'));
        self::assertTrue($responses->has('request-a'));
        self::assertFalse($responses->has('missing'));
        self::assertSame([
            'request-a' => $ok,
            'request-b' => $notFound,
        ], $responses->all());
        self::assertCount(2, $responses);
    }

    /**
     * Responses can be read with array access.
     */
    public function testArrayAccessReadsResponses(): void {

        $response = self::response(200);
        $responses = new Responses(['request-a' => $response]);

        self::assertTrue(isset($responses['request-a']));
        self::assertFalse(isset($responses['missing']));
        self::assertFalse(isset($responses[123]));
        self::assertSame($response, $responses['request-a']);
    }

    /**
     * get() throws when the response is missing.
     */
    public function testGetThrowsExceptionForMissingResponse(): void {

        $this->expectException(ResponseNotFoundException::class);
        $this->expectExceptionMessage('Response not found for request ID "missing".');

        (new Responses([]))->get('missing');
    }

    /**
     * Array access throws when the response is missing.
     */
    public function testOffsetGetThrowsExceptionForMissingResponse(): void {

        $this->expectException(ResponseNotFoundException::class);
        $this->expectExceptionMessage('Response not found for request ID "missing".');

        (new Responses([]))['missing'];
    }

    /**
     * Array access keys must be strings.
     */
    public function testOffsetGetThrowsExceptionForInvalidKey(): void {

        $this->expectException(InvalidResponseException::class);

        (new Responses([]))[123];
    }

    /**
     * The collection is iterable by request ID.
     */
    public function testIteratorReturnsResponsesByRequestId(): void {

        $ok = self::response(200);
        $notFound = self::response(404);
        $responses = new Responses([
            'request-a' => $ok,
            'request-b' => $notFound,
        ]);

        self::assertSame([
            'request-a' => $ok,
            'request-b' => $notFound,
        ], iterator_to_array($responses));
    }

    /**
     * Array write operations are rejected.
     */
    public function testArrayWriteOperationsThrowException(): void {

        $responses = new Responses([]);

        $this->expectException(ImmutableCollectionException::class);

        $responses['request-a'] = self::response(200);
    }

    /**
     * Array unset operations are rejected.
     */
    public function testArrayUnsetOperationsThrowException(): void {

        $responses = new Responses(['request-a' => self::response(200)]);

        $this->expectException(ImmutableCollectionException::class);

        unset($responses['request-a']);
    }

    /**
     * 最小のサンプルResponseクラスを作成
     */
    private static function response(int $statusCode): Response {
        return new Response(
            statusCode:   $statusCode,
            headers:      [],
            body:         null,
            info:         [],
            error:        null,
            errorMessage: '',
        );
    }
}
