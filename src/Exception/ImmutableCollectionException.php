<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Exception;

use Throwable;

/**
 * Thrown when an immutable collection is modified.
 */
final class ImmutableCollectionException extends SimpleCurlException {

    protected int $_defaultCode = 0;

    /**
     * @inheritDoc
     */
    public function __construct(?string $message = null, ?Throwable $previous = null){

        if($message === null || $message === ''){
            $message = 'Collection is immutable';
        }

        parent::__construct($message, $previous);
    }
}