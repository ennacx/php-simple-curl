<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Helper;

use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;
use Ennacx\SimpleCurl\Helper\Internal\HeaderUtils;
use PHPUnit\Framework\TestCase;

/**
 * HeaderUtilsのヘッダー探索・削除処理を検証する。
 */
final class HeaderUtilsTest extends TestCase {

    /**
     * ヘッダー名の大文字小文字を無視して存在確認できることを検証する。
     */
    public function testHasDetectsHeaderCaseInsensitively(): void {

        self::assertTrue(HeaderUtils::has(['content-type' => 'application/json'], 'Content-Type'));
        self::assertTrue(HeaderUtils::has(['Content-Type: application/json'], 'content-type'));
        self::assertTrue(HeaderUtils::has(['Content-Type : application/json'], 'content-type'));
        self::assertFalse(HeaderUtils::has(['Accept' => 'application/json'], 'Content-Type'));
    }

    /**
     * 連想配列形式とヘッダー行形式の両方から指定ヘッダーを削除できることを検証する。
     */
    public function testRemoveDeletesMatchingHeadersCaseInsensitively(): void {

        $headers = [
            'content-type' => 'application/json',
            'Accept' => 'application/json',
            'Content-Type : multipart/form-data',
            'X-Content-Type-Options' => 'nosniff',
        ];

        HeaderUtils::remove($headers, 'Content-Type');

        self::assertSame([
            'Accept' => 'application/json',
            'X-Content-Type-Options' => 'nosniff',
        ], $headers);
    }

    /**
     * ヘッダー値として有効な文字列は例外を投げないことを検証する。
     */
    public function testAssertHeaderValueAcceptsPlainHeaderValue(): void {

        HeaderUtils::assertHeaderValue('User-Agent', 'MyApp/1.0');

        self::assertTrue(true);
    }

    /**
     * ヘッダー名を含む値を不正として扱うことを検証する。
     */
    public function testAssertHeaderValueRejectsHeaderLine(): void {

        $this->expectException(InvalidConfigurationException::class);

        HeaderUtils::assertHeaderValue('User-Agent', 'User-Agent : MyApp/1.0');
    }

    /**
     * 改行を含むヘッダー値を不正として扱うことを検証する。
     */
    public function testAssertHeaderValueRejectsLineBreaks(): void {

        $this->expectException(InvalidConfigurationException::class);

        HeaderUtils::assertHeaderValue('User-Agent', "MyApp/1.0\n");
    }
}
