<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;

/**
 * リダイレクト追跡に関するcURLオプションを保持するConfig。
 */
final readonly class RedirectConfig implements CurlOptionsApplier {

    /**
     * コンストラクタ
     *
     * @param  boolean $follow       リダイレクトを追跡するか
     * @param  int     $maxRedirects 最大リダイレクト回数。-1は無制限
     * @param  boolean $autoReferer  リダイレクト時にRefererを自動設定するか
     * @throws InvalidConfigurationException
     */
    public function __construct(
        public bool $follow       = false,
        public int  $maxRedirects = 10,
        public bool $autoReferer  = true,
    ){
        if($this->maxRedirects < -1){
            throw new InvalidConfigurationException('Max redirects must be -1 or greater.');
        }
    }

    /**
     * リダイレクト追跡を有効にした設定を生成する。
     *
     * @param  int     $maxRedirects
     * @param  boolean $autoReferer
     * @return self
     */
    public static function enabled(int $maxRedirects = 10, bool $autoReferer = true): self {
        return new self(follow: true, maxRedirects: $maxRedirects, autoReferer: $autoReferer);
    }

    /**
     * リダイレクト追跡を無効にした設定を生成する。
     *
     * @return self
     */
    public static function disabled(): self {
        return new self(follow: false, maxRedirects: 0, autoReferer: false);
    }

    /**
     * リダイレクト設定をcURLオプションへ適用する。
     *
     * @param  array<int, mixed>     $options
     * @param  array<string, string> $headers
     * @return void
     */
    public function applyToCurlOptions(array &$options, array &$headers): void {

        $options[CURLOPT_FOLLOWLOCATION] = $this->follow;
        $options[CURLOPT_MAXREDIRS]      = $this->maxRedirects;
        $options[CURLOPT_AUTOREFERER]    = $this->autoReferer;
    }
}
