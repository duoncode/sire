<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

use Duon\Sire\Result;

/** @api */
interface Validator
{
	public function validate(array $data): Result;
}
