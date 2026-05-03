<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\Contract;
use Duon\Sire\Failure;
use Override;

final readonly class Sequence implements Contract\Coercer
{
	#[Override]
	public function coerce(mixed $pristine): Contract\Coercion
	{
		if (
			is_array($pristine)
			&& ($pristine === [] || array_keys($pristine) === range(0, count($pristine) - 1))
		) {
			return new Coercion($pristine, $pristine);
		}

		return new Coercion(
			$pristine,
			$pristine,
			new Failure('type.list'),
		);
	}
}
