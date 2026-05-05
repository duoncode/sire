<?php

declare(strict_types=1);

namespace Duon\Sire\Rule;

use Duon\Sire\Contract;
use Duon\Sire\DslSplitter;
use Duon\Sire\Validation;
use Override;

/** @api */
final class Allowed implements Contract\Rule
{
	public string $message {
		get => '{label} must be an allowed value';
	}

	#[Override]
	public function validate(Contract\Value $value, string ...$args): Contract\Validation
	{
		$allowed = DslSplitter::split($args[0] ?? '', ',');

		return Validation::from(in_array($value->value, $allowed, true));
	}
}
