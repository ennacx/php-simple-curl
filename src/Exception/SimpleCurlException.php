<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use Throwable;

/**
 * Generic exception for PHP Simple cURL.
 *
 * Catch SimpleCurlErrorInterface when all library exceptions should be handled together.
 */
class SimpleCurlException extends SimpleCurlBaseException implements SimpleCurlErrorInterface {

    protected int $_defaultCode = 0;

    /**
     * @param string|null    $message  Error message. A default message is used when empty.
     * @param Throwable|null $previous Previous exception.
     */
    public function __construct(?string $message = null, ?Throwable $previous = null){

        if($message === null || $message === ''){
            $message = 'Internal error';
        }

        parent::__construct($message, $previous);
    }
}
