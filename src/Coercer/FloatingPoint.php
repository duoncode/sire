<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Contract;
use Duon\Sire\Value;
use Override;

final readonly class FloatingPoint implements Contract\Coercer
{
	/** @param array<string, string> $messages */
	public function __construct(
		private array $messages,
	) {}

	#[Override]
	public function coerce(mixed $pristine, string $label): Contract\Value
	{
		if (is_float($pristine) || is_null($pristine)) {
			return new Value($pristine, $pristine);
		}

		if (is_int($pristine)) {
			return new Value((float) $pristine, $pristine);
		}

		$tmp = trim((string) $pristine);

		if (preg_match('/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/', $tmp)) {
			return new Value((float) $tmp, $pristine);
		}

		return new Value(
			$pristine,
			$pristine,
			sprintf($this->messages['float'], $label),
		);
	}
}
