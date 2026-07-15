<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use RuntimeException;
use Throwable;

/**
 * Base class for all PHP Simple cURL exceptions.
 */
abstract class SimpleCurlBaseException extends RuntimeException {

    /** @var array<int|string, mixed> テンプレートメッセージに差し込む属性値 */
    protected array $_attributes = [];

    /** コンストラクタで属性配列を受け取った場合に使用するメッセージテンプレート */
    protected string $_messageTemplate = '';

    /** デフォルトの例外コード */
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
