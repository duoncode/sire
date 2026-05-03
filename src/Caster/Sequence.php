<?php

declare(strict_types=1);

namespace Duon\Sire\Caster;

use Duon\Sire\Contract;
use Duon\Sire\Value;
use Override;

final readonly class Sequence implements Contract\TypeCaster
{
	/** @param array<string, string> $messages */
	public function __construct(
		private array $messages,
	) {}

	#[Override]
	public function cast(mixed $pristine, string $label): Value
	{
		if (
			is_array($pristine)
			&& ($pristine === [] || array_keys($pristine) === range(0, count($pristine) - 1))
		) {
			return new Value($pristine, $pristine);
		}

		return new Value(
			$pristine,
			$pristine,
			sprintf($this->messages['list'], $label),
		);
	}
}
