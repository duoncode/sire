<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\Contract;
use Duon\Sire\Failure;
use Override;

final readonly class Boolean implements Contract\Coercer
{
	#[Override]
	public function coerce(mixed $pristine): Contract\Coercion
	{
		if (is_bool($pristine)) {
			return new Coercion($pristine, $pristine);
		}

		if (!$pristine) {
			return new Coercion(false, $pristine);
		}

		$tmp = strtolower((string) $pristine);

		if (in_array($tmp, ['1', 'on', 'true', 'yes'], true)) {
			return new Coercion(true, $pristine);
		}

		if (in_array($tmp, ['0', 'off', 'false', 'no', 'null'], true)) {
			return new Coercion(false, $pristine);
		}

		return new Coercion(
			$pristine,
			$pristine,
			new Failure('type.bool'),
		);
	}
}
