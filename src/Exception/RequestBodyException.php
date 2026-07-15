<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use Throwable;

/**
 * Thrown when a request body cannot be built.
 *
 * Use this for JSON body, file body, form body, and attachment errors.
 */
final class RequestBodyException extends SimpleCurlException {

    protected int $_defaultCode = 0;

    /**
     * @inheritDoc
     */
    public function __construct(?string $message = null, ?Throwable $previous = null){

        if($message === null || $message === ''){
            $message = 'Invalid Request Body';
        }

        parent::__construct($message, $previous);
    }
}
