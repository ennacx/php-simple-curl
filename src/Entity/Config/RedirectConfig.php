<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;

/**
 * Redirect handling configuration.
 */
final readonly class RedirectConfig implements CurlOptionsApplierInterface {

    /**
     * Creates a redirect config.
     *
     * @param  boolean $follow       Whether redirects should be followed.
     * @param  int     $maxRedirects Maximum redirects. -1 means unlimited.
     * @param  boolean $autoReferer  Whether cURL should automatically set Referer on redirects.
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
     * Creates an enabled redirect config.
     *
     * @param int     $maxRedirects Maximum redirects.
     * @param boolean $autoReferer  Whether cURL should automatically set Referer on redirects.
     */
    public static function enabled(int $maxRedirects = 10, bool $autoReferer = true): self {
        return new self(follow: true, maxRedirects: $maxRedirects, autoReferer: $autoReferer);
    }

    /**
     * Creates a disabled redirect config.
     */
    public static function disabled(): self {
        return new self(follow: false, maxRedirects: 0, autoReferer: false);
    }

    /**
     * @inheritDoc
     */
    public function applyToCurlOptions(array &$options, array &$headers): void {

        $options[CURLOPT_FOLLOWLOCATION] = $this->follow;
        $options[CURLOPT_MAXREDIRS]      = $this->maxRedirects;
        $options[CURLOPT_AUTOREFERER]    = $this->autoReferer;
    }
}
