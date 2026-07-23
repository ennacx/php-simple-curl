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
final class CurlEnvironment {

    /**
     * cURL拡張が実行可能な状態か確認する。
     *
     * @throws InvalidConfigurationException
     */
    public static function assertAvailable(): void {

        if(!extension_loaded('curl') || !function_exists('curl_init')){
            throw new InvalidConfigurationException('The curl extension is required.');
        }
    }
}
