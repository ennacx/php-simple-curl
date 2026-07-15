<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use Throwable;

final class InvalidRequestException extends SimpleCurlException {

    protected int $_defaultCode = 0;

    public function __construct(?string $message = null, ?Throwable $previous = null){

        if($message === null || $message === ''){
            $message = 'Invalid Request';
        }

        parent::__construct($message, $previous);
    }
}