<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Option;

use Ennacx\SimpleCurl\Config\ClientConfig;
use Ennacx\SimpleCurl\Config\CurlOptionsApplierInterface;
use Ennacx\SimpleCurl\Config\RedirectConfig;
use Ennacx\SimpleCurl\Config\TimeoutConfig;

/**
 * Immutable cURL execution options.
 *
 * This object stores execution-level options such as response capture, timeout,
 * redirects, and client metadata. Request body and URL data belong to Request.
 */
final readonly class CurlOptions {

    /**
     * Creates an option set.
     *
     * @template T of CurlOptionsApplierInterface
     * @param boolean                   $captureHeaders Whether response headers should be captured.
     * @param boolean                   $captureBody    Whether response body should be captured.
     * @param array<class-string<T>, T> $config         Config objects keyed by class name.
     */
    public function __construct(
        private bool  $captureHeaders = true,
        private bool  $captureBody    = true,
        private array $config         = [],
    ){
    }

    /**
     * Creates a default option set with optional config objects.
     *
     * @param CurlOptionsApplierInterface ...$config Config objects to apply.
     */
    public static function create(CurlOptionsApplierInterface ...$config): self {
        return (new self())->with(...$config);
    }

    /**
     * Returns a new option set with the given config objects.
     *
     * Config objects are keyed by class name; adding the same config class
     * replaces the previous one.
     *
     * @param CurlOptionsApplierInterface ...$config Config objects to apply.
     */
    public function with(CurlOptionsApplierInterface ...$config): self {

        $applied = $this->config;

        foreach($config as $c){
            $applied[$c::class] = $c;
        }

        return new self(
            captureHeaders: $this->captureHeaders,
            captureBody:    $this->captureBody,
            config:         $applied,
        );
    }

    /**
     * Checks whether a config class is set.
     *
     * @template T of CurlOptionsApplierInterface
     * @param class-string<T> $class Config class name.
     */
    public function has(string $class): bool {
        return (isset($this->config[$class]));
    }

    /**
     * Returns a config object by class name.
     *
     * @template T of CurlOptionsApplierInterface
     * @param  class-string<T> $class Config class name.
     * @return T|null
     */
    public function get(string $class): ?CurlOptionsApplierInterface {

        if(!$this->has($class)){
            return null;
        }

        return $this->config[$class];
    }

    /**
     * Returns a new option set without the given config class.
     *
     * @template T of CurlOptionsApplierInterface
     * @param  class-string<T> $class Config class name.
     */
    public function remove(string $class): self {

        $config = $this->config;
        unset($config[$class]);

        return new self(
            captureHeaders: $this->captureHeaders,
            captureBody:    $this->captureBody,
            config:         $config,
        );
    }

    /**
     * Returns a new option set with response header capture enabled or disabled.
     *
     * @param  boolean $capture Whether response headers should be captured.
     */
    public function captureHeaders(bool $capture = true): self {

        return new self(
            captureHeaders: $capture,
            captureBody:    $this->captureBody,
            config:         $this->config,
        );
    }

    /**
     * Returns a new option set with response body capture enabled or disabled.
     *
     * @param  boolean $capture Whether response body should be captured.
     */
    public function captureBody(bool $capture = true): self {

        return new self(
            captureHeaders: $this->captureHeaders,
            captureBody:    $capture,
            config:         $this->config,
        );
    }

    /**
     * Checks whether response headers should be captured.
     *
     */
    public function isCapturingHeaders(): bool {
        return $this->captureHeaders;
    }

    /**
     * Checks whether response body should be captured.
     *
     */
    public function isCapturingBody(): bool {
        return $this->captureBody;
    }

    /**
     * Returns a new option set with second-based timeout settings.
     *
     * The connection timeout is set to the same value.
     *
     * @param  int $timeoutSec Timeout in seconds.
     */
    public function timeout(int $timeoutSec): self {

        return $this->with(
            TimeoutConfig::seconds(timeoutSec: $timeoutSec, connectTimeoutSec: $timeoutSec),
        );
    }

    /**
     * Returns a new option set with redirect following enabled.
     *
     * @param  int     $maxRedirects Maximum number of redirects.
     * @param  boolean $autoReferer  Whether cURL should automatically set Referer on redirects.
     */
    public function followRedirects(int $maxRedirects = 10, bool $autoReferer = true): self {

        return $this->with(
            RedirectConfig::enabled(maxRedirects: $maxRedirects, autoReferer: $autoReferer),
        );
    }

    /**
     * Returns a new option set with redirect following enabled.
     *
     * Alias of followRedirects().
     *
     */
    public function followRedirect(): self {
        return $this->followRedirects();
    }

    /**
     * Returns a new option set with a User-Agent header.
     *
     * @param  string $userAgent User-Agent value.
     */
    public function userAgent(string $userAgent): self {

        $client  = $this->config[ClientConfig::class] ?? null;
        $referer = ($client instanceof ClientConfig) ? $client->referer : null;

        return $this->with(
            new ClientConfig(userAgent: $userAgent, referer: $referer),
        );
    }

    /**
     * Returns a new option set with a Referer header.
     *
     * @param  string $referer Referer value.
     */
    public function referer(string $referer): self {

        $client    = $this->config[ClientConfig::class] ?? null;
        $userAgent = ($client instanceof ClientConfig) ? $client->userAgent : null;

        return $this->with(
            new ClientConfig(userAgent: $userAgent, referer: $referer),
        );
    }

    /**
     * Returns all configured config objects.
     *
     * @template T of CurlOptionsApplierInterface
     * @return list<T>
     */
    public function getConfig(): array {
        return array_values($this->config);
    }
}
