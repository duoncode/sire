<?php

declare(strict_types=1);

namespace Duon\Sire\Validator;

use Duon\Sire\Contract;
use Override;

/** @api */
final class Minimum implements Contract\Validator
{
	public string $message = 'Lower than the required minimum of %4$s';

	#[Override]
	public function validate(Contract\Value $value, string ...$args): bool
	{
		return (float) $value->value >= (float) ($args[0] ?? null);
	}
}
