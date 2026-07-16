<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;

/**
 * Timeout configuration.
 */
final readonly class TimeoutConfig implements CurlOptionsApplierInterface {

    /**
     * Creates a timeout config.
     *
     * @param  int      $timeoutSeconds             Total timeout in seconds.
     * @param  int      $connectTimeoutSeconds      Connection timeout in seconds.
     * @param  int|null $timeoutMilliseconds        Total timeout in milliseconds.
     * @param  int|null $connectTimeoutMilliseconds Connection timeout in milliseconds.
     * @throws InvalidConfigurationException
     */
    public function __construct(
        public int  $timeoutSeconds             = 30,
        public int  $connectTimeoutSeconds      = 10,
        public ?int $timeoutMilliseconds        = null,
        public ?int $connectTimeoutMilliseconds = null,
    ){

        foreach([
            'timeoutSeconds'             => $this->timeoutSeconds,
            'connectTimeoutSeconds'      => $this->connectTimeoutSeconds,
            'timeoutMilliseconds'        => $this->timeoutMilliseconds,
            'connectTimeoutMilliseconds' => $this->connectTimeoutMilliseconds,
        ] as $name => $value){
            if($value !== null && $value < 0){
                throw new InvalidConfigurationException(sprintf('%s must be 0 or greater.', $name));
            }
        }
    }

    /**
     * Creates a second-based timeout config.
     *
     * @param  int $timeoutSec        Total timeout in seconds.
     * @param  int $connectTimeoutSec Connection timeout in seconds.
     * @return self
     */
    public static function seconds(int $timeoutSec, int $connectTimeoutSec = 10): self {
        return new self($timeoutSec, $connectTimeoutSec);
    }

    /**
     * Creates a millisecond-based timeout config.
     *
     * @param  int $timeoutMs        Total timeout in milliseconds.
     * @param  int $connectTimeoutMs Connection timeout in milliseconds.
     * @return self
     */
    public static function milliseconds(int $timeoutMs, int $connectTimeoutMs = 10000): self {
        return new self(timeoutMilliseconds: $timeoutMs, connectTimeoutMilliseconds: $connectTimeoutMs);
    }

    /**
     * @inheritDoc
     */
    public function applyToCurlOptions(array &$options, array &$headers): void {

        $options[CURLOPT_TIMEOUT]        = $this->timeoutSeconds;
        $options[CURLOPT_CONNECTTIMEOUT] = $this->connectTimeoutSeconds;

        if($this->timeoutMilliseconds !== null){
            $options[CURLOPT_TIMEOUT_MS] = $this->timeoutMilliseconds;
        }

        if($this->connectTimeoutMilliseconds !== null){
            $options[CURLOPT_CONNECTTIMEOUT_MS] = $this->connectTimeoutMilliseconds;
        }
    }
}
