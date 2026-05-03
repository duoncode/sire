<?php

declare(strict_types=1);

namespace Duon\Sire\Caster;

use Duon\Sire\Contract;
use Duon\Sire\Value;
use Override;

final readonly class Integer implements Contract\TypeCaster
{
	/** @param array<string, string> $messages */
	public function __construct(
		private array $messages,
	) {}

	#[Override]
	public function cast(mixed $pristine, string $label): Value
	{
		if (is_int($pristine) || is_null($pristine)) {
			return new Value($pristine, $pristine);
		}

		if (preg_match('/^([0-9]|-[1-9]|-?[1-9][0-9]*)$/i', trim((string) $pristine))) {
			return new Value((int) $pristine, $pristine);
		}

		return new Value(
			$pristine,
			$pristine,
			sprintf($this->messages['int'], $label),
		);
	}
}
