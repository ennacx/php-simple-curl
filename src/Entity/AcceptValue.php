<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

/**
 * Represents a value that can be written to the Accept header.
 */
interface AcceptValue {

    /**
     * Converts the value into an Accept header segment.
     *
     * @return string
     */
    public function toHeaderValue(): string;
}
