<?php
declare(strict_types=1);

/**
 * @internal
 */
namespace Ennacx\SimpleCurl\Helper\Internal;

use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;

/**
 * @internal
 */
final class SslEnvironment {

    /**
     * OpenSSL拡張が実行可能な状態か確認する。
     *
     * @throws InvalidConfigurationException
     */
    public static function assertAvailable(): void {

        if(!extension_loaded('openssl')){
            throw new InvalidConfigurationException('The openssl extension is required.');
        }
    }
}
