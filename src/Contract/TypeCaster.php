<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

use Duon\Sire\Value;

/** @api */
interface TypeCaster
{
	public function cast(mixed $pristine, string $label): Value;
}
