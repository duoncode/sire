<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\CoercionMode;
use Duon\Sire\Contract;
use Duon\Sire\Failure;
use Override;

/** @api */
final class Number implements Contract\Coercer
{
	public string $message {
		get => '{label} must be a number';
	}

	#[Override]
	public function coerce(mixed $pristine, CoercionMode $mode): Contract\Coercion
	{
		if ($mode === CoercionMode::Strict) {
			return self::coerceStrict($pristine);
		}

		if (!self::isCoercible($pristine)) {
			return self::invalid($pristine);
		}

		$value = self::toNumber($pristine);

		return new Coercion($value, $pristine, empty: $value === null);
	}

	private static function coerceStrict(mixed $pristine): Coercion
	{
		if ($pristine === null) {
			return new Coercion(null, null, empty: true);
		}

		return is_int($pristine) || is_float($pristine)
			? new Coercion($pristine, $pristine)
			: self::invalid($pristine);
	}

	private static function invalid(mixed $pristine): Coercion
	{
		return new Coercion(
			$pristine,
			$pristine,
			Failure::invalid(),
			empty: self::isEmpty($pristine),
		);
	}

	private static function isCoercible(mixed $value): bool
	{
		return (
			is_null($value)
			|| is_float($value)
			|| is_int($value)
			|| self::isNumericString(trim((string) $value))
		);
	}

	private static function isNumericString(string $value): bool
	{
		return preg_match('/^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$/', $value) === 1;
	}

	private static function isEmpty(mixed $value): bool
	{
		return $value === null || is_string($value) && trim($value) === '';
	}

	private static function toNumber(mixed $value): int|float|null
	{
		if (is_null($value) || is_int($value) || is_float($value)) {
			return $value;
		}

		return self::toNumericString(trim((string) $value));
	}

	private static function toNumericString(string $value): int|float
	{
		if (self::isIntegerString($value)) {
			return (int) $value;
		}

		return (float) $value;
	}

	private static function isIntegerString(string $value): bool
	{
		return preg_match('/^[-+]?[0-9]+$/', $value) === 1;
	}
}
