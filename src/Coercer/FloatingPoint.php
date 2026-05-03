<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\Contract;
use Duon\Sire\Failure;
use Override;

final readonly class FloatingPoint implements Contract\Coercer
{
	#[Override]
	public function coerce(mixed $pristine, string $label): Contract\Coercion
	{
		if (is_float($pristine) || is_null($pristine)) {
			return new Coercion($pristine, $pristine);
		}

		if (is_int($pristine)) {
			return new Coercion((float) $pristine, $pristine);
		}

		$tmp = trim((string) $pristine);

		if (preg_match('/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/', $tmp)) {
			return new Coercion((float) $tmp, $pristine);
		}

		return new Coercion(
			$pristine,
			$pristine,
			new Failure('type.float'),
		);
	}
}
