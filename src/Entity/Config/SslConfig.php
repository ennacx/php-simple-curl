<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

use Ennacx\SimpleCurl\Enum\SSLVersion;
use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;

/**
 * SSL/TLSに関するcURLオプションを保持するConfig。
 */
final readonly class SslConfig implements CurlOptionsApplier {

    /**
     * コンストラクタ
     *
     * @param  boolean         $verifyPeer      証明書検証を行うか
     * @param  boolean         $verifyHost      ホスト名検証を行うか
     * @param  string|null     $caInfo          CA証明書ファイルパス
     * @param  string|null     $caPath          CA証明書ディレクトリパス
     * @param  SSLVersion|null $version         SSL/TLSバージョン
     * @param  boolean         $verifyStatus    OCSP staplingによる証明書状態検証を行うか
     * @param  string|null     $pinnedPublicKey ピン留め公開鍵
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
     * SSL/TLS検証を有効にした設定を生成する。
     *
     * @return self
     */
    public static function verified(): self {
        return new self();
    }

    /**
     * SSL/TLS検証を無効にした設定を生成する。
     *
     * @return self
     */
    public static function insecure(): self {
        return new self(false, false);
    }

    /**
     * SSL/TLS設定をcURLオプションへ適用する。
     *
     * @param  array<int, mixed>     $options
     * @param  array<string, string> $headers
     * @return void
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
