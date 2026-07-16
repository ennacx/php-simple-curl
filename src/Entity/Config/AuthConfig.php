<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

use Ennacx\SimpleCurl\Enum\CurlAuth;
use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;

/**
 * HTTP authentication configuration.
 */
final readonly class AuthConfig implements CurlOptionsApplierInterface {

    /**
     * Creates an authentication config.
     *
     * @param  CurlAuth    $method      Authentication method.
     * @param  string|null $user        Authentication user.
     * @param  string|null $password    Authentication password.
     * @param  string|null $bearerToken Bearer token for Authorization header.
     * @throws InvalidConfigurationException
     */
    public function __construct(
        public CurlAuth $method,
        public ?string  $user        = null,
        public ?string  $password    = null,
        public ?string  $bearerToken = null,
    ){
        if($this->method !== CurlAuth::NONE && $this->bearerToken === null && ($this->user === null || $this->password === null)){
            throw new InvalidConfigurationException('Authentication user and password are required.');
        }
    }

    /**
     * Creates a config without authentication.
     */
    public static function none(): self {
        return new self(CurlAuth::NONE);
    }

    /**
     * Creates a Basic authentication config.
     *
     * @param string $user     Authentication user.
     * @param string $password Authentication password.
     */
    public static function basic(string $user, string $password): self {
        return new self(CurlAuth::BASIC, $user, $password);
    }

    /**
     * Creates a Bearer token authentication config.
     *
     * @param  string $token Bearer token.
     * @throws InvalidConfigurationException
     */
    public static function bearer(string $token): self {

        $token = trim($token);
        if($token === ''){
            throw new InvalidConfigurationException('Bearer token must not be empty.');
        }

        return new self(CurlAuth::NONE, bearerToken: $token);
    }

    /**
     * @inheritDoc
     */
    public function applyToCurlOptions(array &$options, array &$headers): void {

        if($this->bearerToken !== null){
            $headers['Authorization'] = sprintf('Bearer %s', $this->bearerToken);
        }

        if($this->method !== CurlAuth::NONE){
            $options[CURLOPT_HTTPAUTH] = $this->method->toCurlConst();

            if($this->user !== null && $this->password !== null){
                $options[CURLOPT_USERPWD] = sprintf('%s:%s', $this->user, $this->password);
            }
        }
    }
}
