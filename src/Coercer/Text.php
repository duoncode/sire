<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\Contract;
use Override;

final class Text implements Contract\Coercer
{
	#[Override]
	public function coerce(mixed $pristine, string $label): Contract\Coercion
	{
		if (self::isEmptyTextInput($pristine)) {
			return new Coercion(null, $pristine);
		}

		return new Coercion((string) $pristine, $pristine);
	}

	private static function isEmptyTextInput(mixed $value): bool
	{
		return (
			$value === null
			|| $value === false
			|| $value === 0
			|| $value === 0.0
			|| $value === ''
			|| $value === '0'
			|| $value === []
		);
	}
}
