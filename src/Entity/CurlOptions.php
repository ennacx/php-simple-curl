<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Entity\Config\ClientConfig;
use Ennacx\SimpleCurl\Entity\Config\CurlOptionsApplierImpl;
use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;

/**
 * cURL実行時のオプション設定をまとめる値オブジェクト。
 * Request本体には含めず、通信制御やレスポンス取得方法に関わる設定を保持する。
 */
final readonly class CurlOptions {

    /**
     * コンストラクタ
     *
     * @template T of CurlOptionsApplierImpl
     * @param boolean                   $captureHeaders レスポンスヘッダーをResponseに保持するか
     * @param boolean                   $captureBody    レスポンスボディをResponseに保持するか
     * @param array<class-string<T>, T> $config         `CurlOptionsApplierImpl` のリスト
     */
    public function __construct(
        public  bool  $captureHeaders = true,
        public  bool  $captureBody    = true,
        private array $config         = [],
    ){
    }

    /**
     * デフォルト設定のCurlOptionsを生成する。
     *
     * @template T of CurlOptionsApplierImpl
     * @param  list<T> $config cURLオプションを保持するConfig
     * @return self
     */
    public static function create(CurlOptionsApplierImpl ...$config): self {
        return (new self())->with(...$config);
    }

    /**
     * Configクラスを反映した本クラスを返却する。
     *
     * @param  CurlOptionsApplierImpl ...$config cURLオプションを保持するConfig
     * @return self
     */
    public function with(CurlOptionsApplierImpl ...$config): self {

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
     * 指定Configクラスが設定済みかをチェックする。
     *
     * @template T of CurlOptionsApplierImpl
     * @param class-string<T> $class
     */
    public function has(string $class): bool {
        return (isset($this->config[$class]));
    }

    /**
     * 指定されたConfigクラスを取得する。
     *
     * @template T of CurlOptionsApplierImpl
     * @param  class-string<T> $class
     * @return T|null
     */
    public function get(string $class): ?CurlOptionsApplierImpl {

        if(!$this->has($class)){
            return null;
        }

        return $this->config[$class];
    }

    /**
     * 指定されたConfigクラスを削除する。
     *
     * @template T of CurlOptionsApplierImpl
     * @param  class-string<T> $class
     * @return self
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
     * レスポンスヘッダーをResponseへ保持するか設定する。
     *
     * @param  boolean $capture `true` の場合は `Response::$headers`へ ヘッダー行を格納する
     * @return self
     */
    public function captureHeaders(bool $capture = true): self {

        return new self(
            captureHeaders: $capture,
            captureBody:    $this->captureBody,
            config:         $this->config,
        );
    }

    /**
     * レスポンスボディをResponseへ保持するか設定する。
     *
     * @param  boolean $capture `true` の場合は `Response::$body`へ ボディを格納する
     * @return self
     */
    public function captureBody(bool $capture = true): self {

        return new self(
            captureHeaders: $this->captureHeaders,
            captureBody:    $capture,
            config:         $this->config,
        );
    }

    /**
     * 秒単位のタイムアウト設定を追加する。
     * ※接続タイムアウトも同じ秒数で設定する。
     *
     * @param  int $timeoutSec タイムアウト秒数
     * @return self
     */
    public function timeout(int $timeoutSec): self {

        return $this->with(
            TimeoutConfig::seconds(timeoutSec: $timeoutSec, connectTimeoutSec: $timeoutSec),
        );
    }

    /**
     * リダイレクト追跡を有効にする。
     *
     * @param  int     $maxRedirects 最大リダイレクト回数 [default: `10`]
     * @param  boolean $autoReferer  リダイレクト時にリファラを自動設定するか [default: `true`]
     * @return self
     */
    public function followRedirects(int $maxRedirects = 10, bool $autoReferer = true): self {

        return $this->with(
            RedirectConfig::enabled(maxRedirects: $maxRedirects, autoReferer: $autoReferer),
        );
    }

    /**
     * リダイレクト追跡を有効にする。
     *
     * `followRedirects()` の単数形エイリアス。
     *
     * @return self
     */
    public function followRedirect(): self {
        return $this->followRedirects();
    }

    /**
     * User-Agentヘッダーを送信する設定を追加する。
     *
     * @param  string $userAgent
     * @return self
     */
    public function userAgent(string $userAgent): self {

        $client  = $this->config[ClientConfig::class] ?? null;
        $referer = ($client instanceof ClientConfig) ? $client->referer : null;

        return $this->with(
            new ClientConfig(userAgent: $userAgent, referer: $referer),
        );
    }

    /**
     * Refererヘッダーを送信する設定を追加する。
     *
     * @param  string $referer
     * @return self
     */
    public function referer(string $referer): self {

        $client    = $this->config[ClientConfig::class] ?? null;
        $userAgent = ($client instanceof ClientConfig) ? $client->userAgent : null;

        return $this->with(
            new ClientConfig(userAgent: $userAgent, referer: $referer),
        );
    }

    /**
     * 本クラスに設定されたConfig群を返却する。
     * ※設定していないConfigは返さない。
     *
     * @template T of CurlOptionsApplierImpl
     * @return list<T>
     */
    public function getConfig(): array {
        return array_values($this->config);
    }
}
