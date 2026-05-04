<?php

declare(strict_types=1);

namespace Duon\Sire\Validator;

use Duon\Sire\Contract;
use Duon\Sire\Validation;
use Override;

/** @api */
final class Regex implements Contract\Validator
{
	public string $message {
		get => 'Does not match the required pattern';
	}

	#[Override]
	public function validate(Contract\Value $value, string ...$args): Contract\Validation
	{
		// As regex patterns could contain colons ':' and validator
		// args are separated by colons and split at their position
		// we need to join them again
		$pattern = implode(':', $args);

		if ($pattern === '') {
			return Validation::invalid();
		}

		return Validation::from(preg_match($pattern, $value->value) === 1);
	}
}
