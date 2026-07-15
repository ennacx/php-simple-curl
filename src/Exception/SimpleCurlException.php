<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use Throwable;

class SimpleCurlException extends SimpleCurlBaseException implements SimpleCurlErrorInterface {

    protected int $_defaultCode = 0;

    public function __construct(?string $message = null, ?Throwable $previous = null){

        if($message === null || $message === ''){
            $message = 'Internal error';
        }

        parent::__construct($message, $previous);
    }
}