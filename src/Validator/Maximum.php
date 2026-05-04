<?php

declare(strict_types=1);

namespace Duon\Sire\Validator;

use Duon\Sire\Contract;
use Duon\Sire\Validation;
use Override;

/** @api */
final class Maximum implements Contract\Validator
{
	public string $message {
		get => 'Higher than the allowed maximum of %4$s';
	}

	#[Override]
	public function validate(Contract\Value $value, string ...$args): Contract\Validation
	{
		return Validation::from($value->value <= (float) ($args[0] ?? null));
	}
}
