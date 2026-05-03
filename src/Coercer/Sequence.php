<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\Contract;
use Duon\Sire\Value;
use Override;

final readonly class Sequence implements Contract\Coercer
{
	/** @param array<string, string> $messages */
	public function __construct(
		private array $messages,
	) {}

	#[Override]
	public function coerce(mixed $pristine, string $label): Contract\Coercion
	{
		if (
			is_array($pristine)
			&& ($pristine === [] || array_keys($pristine) === range(0, count($pristine) - 1))
		) {
			return new Coercion(new Value($pristine, $pristine));
		}

		return new Coercion(
			new Value($pristine, $pristine),
			sprintf($this->messages['list'], $label),
		);
	}
}
