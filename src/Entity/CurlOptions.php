<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Entity\Config\AuthConfig;
use Ennacx\SimpleCurl\Entity\Config\ProxyConfig;
use Ennacx\SimpleCurl\Entity\Config\RedirectConfig;
use Ennacx\SimpleCurl\Entity\Config\SslConfig;
use Ennacx\SimpleCurl\Entity\Config\TimeoutConfig;

/**
 * cURL実行時のオプション設定をまとめる値オブジェクト。
 *
 * Request本体には含めず、通信制御やレスポンス取得方法に関わる設定を保持する。
 */
final readonly class CurlOptions {

    /**
     * コンストラクタ
     *
     * @param boolean             $captureHeaders レスポンスヘッダーをResponseに保持するか
     * @param boolean             $captureBody    レスポンスボディをResponseに保持するか
     * @param ProxyConfig|null    $proxy          プロキシー設定
     * @param SslConfig|null      $ssl            SSL/TLS設定
     * @param AuthConfig|null     $auth           認証設定
     * @param TimeoutConfig|null  $timeout        タイムアウト設定
     * @param RedirectConfig|null $redirect       リダイレクト設定
     */
    public function __construct(
        public bool            $captureHeaders = true,
        public bool            $captureBody    = true,
        public ?ProxyConfig    $proxy          = null,
        public ?SslConfig      $ssl            = null,
        public ?AuthConfig     $auth           = null,
        public ?TimeoutConfig  $timeout        = null,
        public ?RedirectConfig $redirect       = null,
    ){
    }

    /**
     * デフォルト設定のCurlOptionsを生成する。
     *
     * @return self
     */
    public static function create(): self {
        return new self();
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
            proxy:          $this->proxy,
            ssl:            $this->ssl,
            auth:           $this->auth,
            timeout:        $this->timeout,
            redirect:       $this->redirect,
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
            proxy:          $this->proxy,
            ssl:            $this->ssl,
            auth:           $this->auth,
            timeout:        $this->timeout,
            redirect:       $this->redirect,
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

        return new self(
            captureHeaders: $this->captureHeaders,
            captureBody:    $this->captureBody,
            proxy:          $this->proxy,
            ssl:            $this->ssl,
            auth:           $this->auth,
            timeout:        TimeoutConfig::seconds(timeoutSec: $timeoutSec, connectTimeoutSec: $timeoutSec),
            redirect:       $this->redirect,
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

        return new self(
            captureHeaders: $this->captureHeaders,
            captureBody:    $this->captureBody,
            proxy:          $this->proxy,
            ssl:            $this->ssl,
            auth:           $this->auth,
            timeout:        $this->timeout,
            redirect:       RedirectConfig::enabled(maxRedirects: $maxRedirects, autoReferer: $autoReferer),
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
     * 自身に設定されたConfig群を返す。設定していないConfigは返さない。
     *
     * @return array<int, ProxyConfig|SslConfig|AuthConfig|TimeoutConfig|RedirectConfig>
     */
    public function getConfig(): array {
        return array_filter([
            $this->proxy,
            $this->ssl,
            $this->auth,
            $this->timeout,
            $this->redirect,
        ]);
    }
}
