<?php

declare(strict_types=1);

namespace Duon\Sire\Validator;

use Duon\Sire\Contract;
use Override;

/** @api */
final class Required implements Contract\Validator
{
	public string $message = 'Required';

	public bool $skipEmpty = false;

	#[Override]
	public function validate(Contract\Value $value, string ...$args): bool
	{
		$val = $value->value;

		if (is_null($val)) {
			return false;
		}

		if (is_array($val) && count($val) === 0) {
			return false;
		}

		return true;
	}
}
