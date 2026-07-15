<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity\Config;

use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;
use Ennacx\SimpleCurl\Static\HeaderUtils;

/**
 * リクエスト時のクライアント情報に関するcURLオプションを保持するConfig。
 */
final readonly class ClientConfig implements CurlOptionsApplier {

    /**
     * コンストラクタ
     *
     * @param  string|null $userAgent ユーザーエージェント
     * @param  string|null $referer   リファラー
     * @throws InvalidConfigurationException
     */
    public function __construct(
        public ?string $userAgent = null,
        public ?string $referer   = null,
    ){
        if($this->userAgent !== null && trim($this->userAgent) === ''){
            throw new InvalidConfigurationException('User-Agent must not be empty.');
        }

        if($this->referer !== null && trim($this->referer) === ''){
            throw new InvalidConfigurationException('Referer must not be empty.');
        }
    }

    /**
     * クライアント設定をcURLオプションと送信ヘッダーへ適用する。
     *
     * @param  array<int, mixed>     $options
     * @param  array<string, string> $headers
     * @return void
     */
    public function applyToCurlOptions(array &$options, array &$headers): void {

        if($this->userAgent !== null && !HeaderUtils::has($headers, 'User-Agent')){
            $headers['User-Agent'] = $this->userAgent;
        }

        if($this->referer !== null && !HeaderUtils::has($headers, 'Referer')){
            $headers['Referer'] = $this->referer;
        }
    }
}
