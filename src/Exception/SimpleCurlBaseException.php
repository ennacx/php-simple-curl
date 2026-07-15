<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use RuntimeException;
use Throwable;

/**
 * Base class for all PHP Simple cURL exceptions.
 */
abstract class SimpleCurlBaseException extends RuntimeException {

    /** @var array<int|string, mixed> Message attributes used by templated exceptions. */
    protected array $_attributes = [];

    /** Message template used when the constructor receives an attribute array. */
    protected string $_messageTemplate = '';

    /** Default exception code. */
    protected int $_defaultCode = 0;

    /**
     * @param array<int|string, mixed>|string $message  Message string or template attributes.
     * @param Throwable|null                  $previous Previous exception.
     */
    public function __construct(array|string $message = '', ?Throwable $previous = null){

        if(is_array($message)){
            $this->_attributes = $message;
            $message = vsprintf($this->_messageTemplate, $message);
        }

        parent::__construct($message, $this->_defaultCode, $previous);
    }
}
