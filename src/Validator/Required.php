<?php

declare(strict_types=1);

namespace Duon\Sire\Validator;

use Duon\Sire\Contract;
use Duon\Sire\Validation;
use Override;

/** @api */
final class Required implements Contract\ValidatesEmpty
{
	public string $message {
		get => 'Required';
	}

	#[Override]
	public function validate(Contract\Value $value, string ...$args): Contract\Validation
	{
		$val = $value->value;

		if (is_null($val)) {
			return Validation::invalid();
		}

		if (is_array($val) && count($val) === 0) {
			return Validation::invalid();
		}

		return Validation::valid();
	}
}
