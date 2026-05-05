<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface Rule
{
	public string $message { get; }

	public function validate(Value $value, string ...$args): Validation;
}
