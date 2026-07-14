<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

use Ennacx\SimpleCurl\Enum\ProxyAuth;
use Ennacx\SimpleCurl\Enum\ProxyProtocol;
use InvalidArgumentException;

/**
 * プロキシー接続に関するcURLオプションを保持するConfig。
 */
final readonly class ProxyConfig implements CurlOptionsApplier {

    /**
     * コンストラクタ
     *
     * @param string        $address    プロキシーアドレス
     * @param int           $port       プロキシーポート
     * @param ProxyProtocol $protocol   プロキシープロトコル
     * @param bool|null     $httpTunnel プロキシーのプロトコルがHTTPの場合、HTTPトンネルを有効にするかどうか
     * @param ProxyAuth     $authMethod プロキシーの認証方法
     * @param string|null   $user       プロキシー認証時のユーザー名
     * @param string|null   $password   プロキシー認証時のパスワード
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
            throw new InvalidArgumentException('Proxy address must not be empty.');
        } else if($this->port < 1 || $this->port > 65535){
            throw new InvalidArgumentException('Proxy port must be between 1 and 65535.');
        } else if($this->authMethod !== ProxyAuth::NONE && ($this->user === null || $this->password === null)){
            throw new InvalidArgumentException('Proxy authentication user and password are required.');
        }
    }

    /**
     * プロキシー設定をcURLオプションへ適用する。
     *
     * @param  array<int, mixed>     $options
     * @param  array<string, string> $headers
     * @return void
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
     * `ProxyConfig::http('host')` のように、Enumの `ProxyProtocol` 名からConfigを生成する。
     *
     * @param  string $method 呼び出された静的メソッド名
     * @param  array  $args   ProxyConfig生成引数
     * @return self
     */
    public static function __callStatic(string $method, array $args): self {

        // プロトコル変換
        $protocol = self::findProtocol($method);
        if($protocol === null){
            throw new InvalidArgumentException(sprintf('Invalid proxy protocol: %s', $method));
        }

        // アドレス取得
        $address = $args['address'] ?? $args[0] ?? null;
        if(!is_string($address)){
            throw new InvalidArgumentException('Proxy address is required.');
        }

        return new self(
            address: trim($address),
            port: $args['port'] ?? $args[1] ?? $protocol->defaultPort(),
            protocol: $protocol,
            httpTunnel: $args['httpTunnel'] ?? $args[2] ?? null,
            authMethod: $args['authMethod'] ?? $args[3] ?? ProxyAuth::NONE,
            user: $args['user'] ?? $args[4] ?? null,
            password: $args['password'] ?? $args[5] ?? null,
        );
    }

    /**
     * メソッド名から `ProxyProtocol` を取得する。
     *
     * @param  string $method
     * @return ProxyProtocol|null
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
