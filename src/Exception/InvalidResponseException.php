<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use Throwable;

/**
 * Thrown when response data cannot be interpreted.
 *
 * Use this for response parsing errors such as JSON decode failures.
 */
final class InvalidResponseException extends SimpleCurlException {

    protected int $_defaultCode = 0;

    /**
     * @inheritDoc
     */
    public function __construct(?string $message = null, ?Throwable $previous = null){

        if($message === null || $message === ''){
            $message = 'Invalid Response';
        }

        parent::__construct($message, $previous);
    }
}
