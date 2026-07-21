<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Test\Config;

use Ennacx\SimpleCurl\Config\AuthConfig;
use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * AuthConfigの入力検証を検証する。
 */
final class AuthConfigTest extends TestCase {

    /**
     * Bearerトークンだけを受け付けることを検証する。
     */
    public function testBearerAcceptsToken(): void {

        $config = AuthConfig::bearer('token');

        self::assertSame('token', $config->bearerToken);
    }

    /**
     * 空のBearerトークンを不正として扱うことを検証する。
     */
    public function testBearerRejectsEmptyToken(): void {

        $this->expectException(InvalidConfigurationException::class);

        AuthConfig::bearer(' ');
    }

    /**
     * Authorizationヘッダー行そのものをBearerトークンとして渡した場合に不正として扱うことを検証する。
     */
    public function testBearerRejectsAuthorizationHeaderLine(): void {

        $this->expectException(InvalidConfigurationException::class);

        AuthConfig::bearer('Authorization : Bearer token');
    }

    /**
     * Bearerスキーム付きの値をBearerトークンとして渡した場合に不正として扱うことを検証する。
     */
    public function testBearerRejectsAuthorizationScheme(): void {

        $this->expectException(InvalidConfigurationException::class);

        AuthConfig::bearer('Bearer token');
    }
}
