<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Enum;

interface FromValueImpl {

    public static function fromValue(int $value): self;
}