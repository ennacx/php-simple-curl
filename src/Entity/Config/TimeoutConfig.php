<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

use InvalidArgumentException;

/**
 * タイムアウトに関するcURLオプションを保持するConfig。
 */
final readonly class TimeoutConfig implements CurlOptionsApplier {

    /**
     * コンストラクタ
     *
     * @param int      $timeoutSeconds             全体タイムアウト秒数
     * @param int      $connectTimeoutSeconds      接続タイムアウト秒数
     * @param int|null $timeoutMilliseconds        全体タイムアウトミリ秒数
     * @param int|null $connectTimeoutMilliseconds 接続タイムアウトミリ秒数
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
                throw new InvalidArgumentException(sprintf('%s must be 0 or greater.', $name));
            }
        }
    }

    /**
     * 秒単位のタイムアウト設定を生成する。
     *
     * @param  int $timeoutSec
     * @param  int $connectTimeoutSec
     * @return self
     */
    public static function seconds(int $timeoutSec, int $connectTimeoutSec = 10): self {
        return new self($timeoutSec, $connectTimeoutSec);
    }

    /**
     * ミリ秒単位のタイムアウト設定を生成する。
     *
     * @param  int $timeoutMs
     * @param  int $connectTimeoutMs
     * @return self
     */
    public static function milliseconds(int $timeoutMs, int $connectTimeoutMs = 10000): self {
        return new self(timeoutMilliseconds: $timeoutMs, connectTimeoutMilliseconds: $connectTimeoutMs);
    }

    /**
     * タイムアウト設定をcURLオプションへ適用する。
     *
     * @param  array<int, mixed>     $options
     * @param  array<string, string> $headers
     * @return void
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
