<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use Throwable;

/**
 * Thrown when a response is not found in a response collection.
 */
final class ResponseNotFoundException extends SimpleCurlException {

    protected int $_defaultCode = 0;

    /**
     * @inheritDoc
     */
    public function __construct(?string $message = null, ?Throwable $previous = null){

        if($message === null || $message === ''){
            $message = 'Response not found';
        }

        parent::__construct($message, $previous);
    }
}
