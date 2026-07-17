<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Config;

use Ennacx\SimpleCurl\Enum\ProxyAuth;
use Ennacx\SimpleCurl\Enum\ProxyProtocol;
use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;

/**
 * Proxy connection configuration.
 */
final readonly class ProxyConfig implements CurlOptionsApplierInterface {

    /**
     * Creates a proxy config.
     *
     * @param  string        $address    Proxy host or address.
     * @param  int           $port       Proxy port.
     * @param  ProxyProtocol $protocol   Proxy protocol.
     * @param  bool|null     $httpTunnel Whether HTTP proxy tunneling should be enabled.
     * @param  ProxyAuth     $authMethod Proxy authentication method.
     * @param  string|null   $user       Proxy authentication user.
     * @param  string|null   $password   Proxy authentication password.
     * @throws InvalidConfigurationException
     */
    public function __construct(
        public string        $address,
        public int           $port       = 0,
        public ProxyProtocol $protocol   = ProxyProtocol::HTTP,
        public ?bool         $httpTunnel = null,
        public ProxyAuth     $authMethod = ProxyAuth::NONE,
        public ?string       $user       = null,
        public ?string       $password   = null,
    ){

        if(trim($this->address) === ''){
            throw new InvalidConfigurationException('Proxy address must not be empty.');
        } else if($this->port < 1 || $this->port > 65535){
            throw new InvalidConfigurationException('Proxy port must be between 1 and 65535.');
        } else if($this->authMethod !== ProxyAuth::NONE && ($this->user === null || $this->password === null)){
            throw new InvalidConfigurationException('Proxy authentication user and password are required.');
        }
    }

    /**
     * Creates a proxy config from a ProxyProtocol case name.
     *
     * Example: ProxyConfig::http('proxy.example.com').
     *
     * @param  string $method Called static method name.
     * @param  array  $args   ProxyConfig constructor arguments.
     * @throws InvalidConfigurationException
     */
    public static function __callStatic(string $method, array $args): self {

        $protocol = self::findProtocol($method);
        if($protocol === null){
            throw new InvalidConfigurationException(sprintf('Invalid proxy protocol: %s', $method));
        }

        $address = $args['address'] ?? $args[0] ?? null;
        if(!is_string($address)){
            throw new InvalidConfigurationException('Proxy address is required.');
        }

        return new self(
            address:    trim($address),
            port:       $args['port'] ?? $args[1] ?? $protocol->defaultPort(),
            protocol:   $protocol,
            httpTunnel: $args['httpTunnel'] ?? $args[2] ?? null,
            authMethod: $args['authMethod'] ?? $args[3] ?? ProxyAuth::NONE,
            user:       $args['user'] ?? $args[4] ?? null,
            password:   $args['password'] ?? $args[5] ?? null,
        );
    }

    /**
     * @inheritDoc
     */
    public function applyToCurlOptions(array &$options, array &$headers): void {

        $options[CURLOPT_PROXY]     = $this->address;
        $options[CURLOPT_PROXYPORT] = $this->port;
        $options[CURLOPT_PROXYTYPE] = $this->protocol->toCurlConst();

        if($this->httpTunnel !== null){
            $options[CURLOPT_HTTPPROXYTUNNEL] = $this->httpTunnel;
        }

        if($this->authMethod !== ProxyAuth::NONE){
            $options[CURLOPT_PROXYAUTH] = $this->authMethod->toCurlConst();
            if($this->user !== null && $this->password !== null){
                $options[CURLOPT_PROXYUSERPWD] = sprintf('%s:%s', $this->user, $this->password);
            }
        }
    }

    /**
     * メソッド名からProxyProtocolを取得する。
     *
     * @param string $method 'http' や 'socks5'
     */
    private static function findProtocol(string $method): ?ProxyProtocol {

        $method = strtolower($method);

        foreach(ProxyProtocol::cases() as $protocol){
            if(strtolower($protocol->name) === $method){
                return $protocol;
            }
        }

        return null;
    }
}
