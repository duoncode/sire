<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface ValidatorRegistry
{
	public function get(string $name): ?Validator;
}
