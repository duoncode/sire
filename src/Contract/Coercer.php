<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

use Duon\Sire\Value;

/** @api */
interface Coercer
{
	public function coerce(mixed $pristine, string $label): Value;
}
