<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface Value
{
	public mixed $value { get; }

	public mixed $pristine { get; }

	public array|string|null $error { get; }
}
