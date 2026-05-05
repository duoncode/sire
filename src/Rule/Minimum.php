<?php

declare(strict_types=1);

namespace Duon\Sire\Rule;

use Duon\Sire\Contract;
use Duon\Sire\Validation;
use Override;

/** @api */
final class Minimum implements Contract\Rule
{
	public string $message {
		get => '{label} must be at least {arg1}';
	}

	#[Override]
	public function validate(Contract\Value $value, string ...$args): Contract\Validation
	{
		return Validation::from((float) $value->value >= (float) ($args[0] ?? null));
	}
}
