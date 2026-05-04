<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

use Duon\Sire\Failure;

/** @api */
interface Validation
{
	public ?Failure $failure { get; }
}
