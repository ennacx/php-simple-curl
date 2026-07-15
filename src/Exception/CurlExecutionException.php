<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use Throwable;

/**
 * Thrown when cURL cannot be initialized or executed.
 *
 * Use this for cURL runtime failures, not for HTTP error responses.
 */
final class CurlExecutionException extends SimpleCurlException {

    protected int $_defaultCode = 0;

    /**
     * @inheritDoc
     */
    public function __construct(?string $message = null, ?Throwable $previous = null){

        if($message === null || $message === ''){
            $message = 'cURL execution failed';
        }

        parent::__construct($message, $previous);
    }
}
