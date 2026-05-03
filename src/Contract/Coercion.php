<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

use Duon\Sire\Failure;

/** @api */
interface Coercion
{
	public mixed $value { get; }

	public mixed $pristine { get; }

	public ?Failure $failure { get; }
}
