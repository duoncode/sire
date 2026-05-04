<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\Contract;
use Duon\Sire\Failure;
use Override;

/** @api */
final class Integer implements Contract\Coercer
{
	public string $message {
		get => 'Invalid number';
	}

	#[Override]
	public function coerce(mixed $pristine): Contract\Coercion
	{
		if (is_int($pristine) || is_null($pristine)) {
			return new Coercion($pristine, $pristine);
		}

		if (preg_match('/^([0-9]|-[1-9]|-?[1-9][0-9]*)$/i', trim((string) $pristine))) {
			return new Coercion((int) $pristine, $pristine);
		}

		return new Coercion(
			$pristine,
			$pristine,
			Failure::invalid(),
		);
	}
}
