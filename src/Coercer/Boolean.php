<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\CoercionMode;
use Duon\Sire\Contract;
use Duon\Sire\Failure;
use Override;

/** @api */
final class Boolean implements Contract\Coercer
{
	public string $message {
		get => '{label} must be true or false';
	}

	#[Override]
	public function coerce(mixed $pristine, CoercionMode $mode): Contract\Coercion
	{
		if ($pristine === null) {
			return new Coercion(null, null, empty: true);
		}

		if (is_bool($pristine)) {
			return new Coercion($pristine, $pristine);
		}

		if ($mode === CoercionMode::Strict) {
			return self::invalid($pristine);
		}

		$value = self::toBoolean($pristine);

		return $value === null
			? self::invalid($pristine)
			: new Coercion($value, $pristine);
	}

	private static function invalid(mixed $pristine): Coercion
	{
		return new Coercion(
			$pristine,
			$pristine,
			Failure::invalid(),
		);
	}

	private static function toBoolean(mixed $value): ?bool
	{
		return match (true) {
			$value === 1 => true,
			$value === 0 => false,
			is_string($value) => self::toStringBoolean($value),
			default => null,
		};
	}

	private static function toStringBoolean(string $value): ?bool
	{
		return match (strtolower(trim($value))) {
			'1', 'true', 'on', 'yes' => true,
			'0', 'false', 'off', 'no' => false,
			default => null,
		};
	}
}
