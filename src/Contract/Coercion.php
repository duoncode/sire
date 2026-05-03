<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface Coercion
{
	public Value $value { get; }

	public ?string $error { get; }
}
