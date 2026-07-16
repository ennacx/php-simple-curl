<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Request;

/**
 * Represents a value that can be written to the Accept header.
 */
interface AcceptValueInterface {

    /**
     * Converts the value into an Accept header segment.
     */
    public function toHeaderValue(): string;
}
