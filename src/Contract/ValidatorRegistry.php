<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

use Duon\Sire\Validator;

/** @api */
interface ValidatorRegistry
{
	/** @return array<string, Validator> */
	public function all(): array;
}
