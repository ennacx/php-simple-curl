<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Option;

use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;
use Ennacx\SimpleCurl\Option\RawCurlOptions;
use PHPUnit\Framework\TestCase;

/**
 * RawCurlOptionsのcollection-like APIを検証する。
 */
final class RawCurlOptionsTest extends TestCase {

    /**
     * raw optionを保持し、immutableに追加・削除できることを検証する。
     */
    public function testCanReadAddAndRemoveRawOptions(): void {

        $raw = RawCurlOptions::create([
            CURLOPT_TIMEOUT => 10,
        ]);

        $updated = $raw
            ->with(CURLOPT_RETURNTRANSFER, null)
            ->without(CURLOPT_TIMEOUT);

        self::assertTrue($raw->has(CURLOPT_TIMEOUT));
        self::assertFalse($raw->has(CURLOPT_RETURNTRANSFER));

        self::assertFalse($updated->has(CURLOPT_TIMEOUT));
        self::assertTrue($updated->has(CURLOPT_RETURNTRANSFER));
        self::assertNull($updated->get(CURLOPT_RETURNTRANSFER));
        self::assertNull($updated->find(CURLOPT_RETURNTRANSFER));
        self::assertNull($updated->find(CURLOPT_TIMEOUT));
        self::assertCount(1, $updated);
        self::assertSame([CURLOPT_RETURNTRANSFER => null], $updated->all());
    }

    /**
     * 存在しないraw optionをget()した場合に例外を投げることを検証する。
     */
    public function testGetThrowsExceptionForMissingOption(): void {

        $this->expectException(InvalidConfigurationException::class);

        RawCurlOptions::create([])->get(CURLOPT_TIMEOUT);
    }

    /**
     * raw optionのキーが整数でない場合に例外を投げることを検証する。
     */
    public function testCreateRejectsNonIntegerOptionKey(): void {

        $this->expectException(InvalidConfigurationException::class);

        RawCurlOptions::create([
            'CURLOPT_TIMEOUT' => 10,
        ]);
    }
}
