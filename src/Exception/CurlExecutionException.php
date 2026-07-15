<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use Throwable;

final class CurlExecutionException extends SimpleCurlException {

    protected int $_defaultCode = 0;

    public function __construct(?string $message = null, ?Throwable $previous = null){

        if($message === null || $message === ''){
            $message = 'cURL execution failed';
        }

        parent::__construct($message, $previous);
    }
}