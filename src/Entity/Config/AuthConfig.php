<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

use Ennacx\SimpleCurl\Enum\CurlAuth;
use InvalidArgumentException;

/**
 * HTTP認証に関するcURLオプションを保持するConfig。
 */
final readonly class AuthConfig implements CurlOptionsApplier {

    /**
     * コンストラクタ
     *
     * @param CurlAuth    $method      認証方式
     * @param string|null $user        認証ユーザー名
     * @param string|null $password    認証パスワード
     * @param string|null $bearerToken Bearerトークン
     */
    public function __construct(
        public CurlAuth $method,
        public ?string  $user        = null,
        public ?string  $password    = null,
        public ?string  $bearerToken = null,
    ){
        if($this->method !== CurlAuth::NONE && $this->bearerToken === null && ($this->user === null || $this->password === null)){
            throw new InvalidArgumentException('Authentication user and password are required.');
        }
    }

    /**
     * 認証なしの設定を生成する。
     *
     * @return self
     */
    public static function none(): self {
        return new self(CurlAuth::NONE);
    }

    /**
     * Basic認証の設定を生成する。
     *
     * @param  string $user
     * @param  string $password
     * @return self
     */
    public static function basic(string $user, string $password): self {
        return new self(CurlAuth::BASIC, $user, $password);
    }

    /**
     * Authorization: Bearer ヘッダーを送信する設定を生成する。
     *
     * @param  string $token
     * @return self
     */
    public static function bearer(string $token): self {
        $token = trim($token);
        if($token === ''){
            throw new InvalidArgumentException('Bearer token must not be empty.');
        }

        return new self(CurlAuth::NONE, bearerToken: $token);
    }

    /**
     * 認証設定をcURLオプションと送信ヘッダーへ適用する。
     *
     * @param  array<int, mixed>     $options
     * @param  array<string, string> $headers
     * @return void
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
