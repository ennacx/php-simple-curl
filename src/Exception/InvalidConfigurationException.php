<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use Throwable;

/**
 * Thrown when cURL options or config objects are invalid.
 */
final class InvalidConfigurationException extends SimpleCurlException {

    protected int $_defaultCode = 0;

    /**
     * @inheritDoc
     */
    public function __construct(?string $message = null, ?Throwable $previous = null){

        if($message === null || $message === ''){
            $message = 'Invalid Configuration';
        }

        parent::__construct($message, $previous);
    }
}
