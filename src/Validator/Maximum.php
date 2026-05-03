<?php

declare(strict_types=1);

namespace Duon\Sire\Validator;

use Duon\Sire\Contract;
use Override;

/** @api */
final class Maximum implements Contract\Validator
{
	public string $message = 'Higher than the allowed maximum of %4$s';

	#[Override]
	public function validate(Contract\Value $value, string ...$args): bool
	{
		return $value->value <= (float) ($args[0] ?? null);
	}
}
