<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

/**
 * Applies a config object to cURL options and request headers.
 */
interface CurlOptionsApplierInterface {

    /**
     * Applies config values to cURL options and headers.
     *
     * @param  array<int, mixed>     $options cURL options for curl_setopt_array().
     * @param  array<string, string> $headers Request headers.
     * @return void
     */
    public function applyToCurlOptions(array &$options, array &$headers): void;
}
