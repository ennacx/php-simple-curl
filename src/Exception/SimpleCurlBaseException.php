<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use RuntimeException;
use Throwable;

abstract class SimpleCurlBaseException extends RuntimeException {

    protected array $_attributes = [];

    protected string $_messageTemplate = '';

    protected int $_defaultCode = 0;

    public function __construct(array|string $message = '', ?Throwable $previous = null){

        if(is_array($message)){
            $this->_attributes = $message;
            $message = vsprintf($this->_messageTemplate, $message);
        }

        parent::__construct($message, $this->_defaultCode, $previous);
    }
}