<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

use Duon\Sire\Result;

/** @api */
interface Shape
{
	public function validate(array $data, int $level = 1): Result;
}
