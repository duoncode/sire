<?php

declare(strict_types=1);

namespace Duon\Sire\Validator;

use Duon\Sire\Contract;
use Duon\Sire\DslSplitter;
use Override;

/** @api */
final class Allowed implements Contract\Validator
{
	public string $message = 'Invalid value';

	public bool $skipEmpty = true;

	#[Override]
	public function validate(Contract\Value $value, string ...$args): bool
	{
		$allowed = DslSplitter::split($args[0] ?? '', ',');

		return in_array($value->value, $allowed, true);
	}
}
