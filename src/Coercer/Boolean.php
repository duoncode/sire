<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Contract;
use Duon\Sire\Value;
use Override;

final readonly class Boolean implements Contract\Coercer
{
	/** @param array<string, string> $messages */
	public function __construct(
		private array $messages,
	) {}

	#[Override]
	public function coerce(mixed $pristine, string $label): Value
	{
		if (is_bool($pristine)) {
			return new Value($pristine, $pristine);
		}

		if (!$pristine) {
			return new Value(false, $pristine);
		}

		$tmp = strtolower((string) $pristine);

		if (in_array($tmp, ['1', 'on', 'true', 'yes'], true)) {
			return new Value(true, $pristine);
		}

		if (in_array($tmp, ['0', 'off', 'false', 'no', 'null'], true)) {
			return new Value(false, $pristine);
		}

		return new Value(
			$pristine,
			$pristine,
			sprintf($this->messages['bool'], $label),
		);
	}
}
