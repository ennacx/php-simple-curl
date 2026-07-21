<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Config;

use Ennacx\SimpleCurl\Exception\InvalidConfigurationException;
use Ennacx\SimpleCurl\Helper\Internal\HeaderUtils;

/**
 * Client metadata configuration.
 */
final readonly class ClientConfig implements CurlOptionsApplierInterface {

    /**
     * Creates a client metadata config.
     *
     * @param  string|null $userAgent User-Agent header value.
     * @param  string|null $referer   Referer header value.
     * @throws InvalidConfigurationException
     */
    public function __construct(
        public ?string $userAgent = null,
        public ?string $referer   = null,
    ){
        if($this->userAgent !== null){
            HeaderUtils::assertHeaderValue('User-Agent', $this->userAgent);
        }

        if($this->referer !== null){
            HeaderUtils::assertHeaderValue('Referer', $this->referer);
        }
    }

    /**
     * @inheritDoc
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
