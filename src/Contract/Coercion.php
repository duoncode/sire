<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface Coercion
{
	public mixed $value { get; }

	public mixed $pristine { get; }

	public ?string $error { get; }
}
