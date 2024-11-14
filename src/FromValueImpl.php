<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl;

interface FromValueImpl {

	public static function fromValue(int $value): self;
}