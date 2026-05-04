<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface Coercer
{
	public string $message { get; }

	public function coerce(mixed $pristine): Coercion;
}
