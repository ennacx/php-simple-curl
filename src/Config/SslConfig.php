<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Config;

use Ennacx\SimpleCurl\Enum\SSLVersion;
use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;
use Ennacx\SimpleCurl\Helper\Internal\SslEnvironment;

/**
 * SSL/TLS verification configuration.
 */
final readonly class SslConfig implements CurlOptionsApplierInterface {

    /**
     * Creates an SSL/TLS config.
     *
     * @param  boolean         $verifyPeer      Whether peer certificate verification should be enabled.
     * @param  boolean         $verifyHost      Whether host name verification should be enabled.
     * @param  string|null     $caInfo          CA certificate file path.
     * @param  string|null     $caPath          CA certificate directory path.
     * @param  SSLVersion|null $version         SSL/TLS version.
     * @param  boolean         $verifyStatus    Whether OCSP stapling verification should be enabled.
     * @param  string|null     $pinnedPublicKey Pinned public key.
     * @throws InvalidConfigurationException
     */
    public function __construct(
        public bool        $verifyPeer      = true,
        public bool        $verifyHost      = true,
        public ?string     $caInfo          = null,
        public ?string     $caPath          = null,
        public ?SSLVersion $version         = null,
        public bool        $verifyStatus    = false,
        public ?string     $pinnedPublicKey = null,
    ){
        // OpenSSL拡張が実行可能な状態か確認
        SslEnvironment::assertAvailable();

        if($this->caInfo !== null && !is_file($this->caInfo)){
            throw new InvalidConfigurationException('CA info file not found.');
        }

        if($this->caPath !== null && !is_dir($this->caPath)){
            throw new InvalidConfigurationException('CA path directory not found.');
        }

        if($this->pinnedPublicKey !== null && trim($this->pinnedPublicKey) === ''){
            throw new InvalidConfigurationException('Pinned public key must not be empty.');
        }
    }

    /**
     * Creates a verified SSL/TLS config.
     *
     * @throws InvalidConfigurationException
     */
    public static function verified(): self {
        return new self;
    }

    /**
     * Creates an SSL/TLS config with verification disabled.
     *
     * @throws InvalidConfigurationException
     */
    public static function insecure(): self {
        return new self(false, false);
    }

    /**
     * @inheritDoc
     */
    public function applyToCurlOptions(array &$options, array &$headers): void {

        $options[CURLOPT_SSL_VERIFYPEER] = $this->verifyPeer;
        $options[CURLOPT_SSL_VERIFYHOST] = ($this->verifyHost) ? 2 : 0;
        $options[CURLOPT_SSL_VERIFYSTATUS] = $this->verifyStatus;

        if($this->caInfo !== null){
            $options[CURLOPT_CAINFO] = $this->caInfo;
        }

        if($this->caPath !== null){
            $options[CURLOPT_CAPATH] = $this->caPath;
        }

        if($this->version !== null){
            $options[CURLOPT_SSLVERSION] = $this->version->toCurlConst();
        }

        if($this->pinnedPublicKey !== null){
            $options[CURLOPT_PINNEDPUBLICKEY] = $this->pinnedPublicKey;
        }
    }
}
