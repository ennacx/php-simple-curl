<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Static;

use Ennacx\SimpleCurl\Static\HeaderUtils;
use PHPUnit\Framework\TestCase;

/**
 * HeaderUtilsのヘッダー探索・削除処理を検証する。
 */
final class HeaderUtilsTest extends TestCase {

    /**
     * ヘッダー名の大文字小文字を無視して存在確認できることを検証する。
     *
     * @return void
     */
    public function testHasDetectsHeaderCaseInsensitively(): void {

        self::assertTrue(HeaderUtils::has(['content-type' => 'application/json'], 'Content-Type'));
        self::assertTrue(HeaderUtils::has(['Content-Type: application/json'], 'content-type'));
        self::assertFalse(HeaderUtils::has(['Accept' => 'application/json'], 'Content-Type'));
    }

    /**
     * 連想配列形式とヘッダー行形式の両方から指定ヘッダーを削除できることを検証する。
     *
     * @return void
     */
    public function testRemoveDeletesMatchingHeadersCaseInsensitively(): void {

        $headers = [
            'content-type' => 'application/json',
            'Accept' => 'application/json',
            'Content-Type: multipart/form-data',
            'X-Content-Type-Options' => 'nosniff',
        ];

        HeaderUtils::remove($headers, 'Content-Type');

        self::assertSame([
            'Accept' => 'application/json',
            'X-Content-Type-Options' => 'nosniff',
        ], $headers);
    }
}
