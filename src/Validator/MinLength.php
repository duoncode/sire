<?php

declare(strict_types=1);

namespace Duon\Sire\Validator;

use Duon\Sire\Contract;
use Override;

/** @api */
final class MinLength implements Contract\Validator
{
	public string $message = 'Shorter than the minimum length of %4$s characters';

	#[Override]
	public function validate(Contract\Value $value, string ...$args): bool
	{
		return strlen($value->value) >= (int) ($args[0] ?? null);
	}
}
