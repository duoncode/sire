<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

use Duon\Sire\CoercionMode;

/** @api */
interface Coercer
{
	public string $message { get; }

	public function coerce(mixed $pristine, CoercionMode $mode): Coercion;
}
