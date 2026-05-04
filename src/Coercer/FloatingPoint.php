<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\Contract;
use Duon\Sire\Failure;
use Override;

/** @api */
final class FloatingPoint implements Contract\Coercer
{
	public string $message {
		get => 'Invalid number';
	}

	#[Override]
	public function coerce(mixed $pristine): Contract\Coercion
	{
		if (!self::isCoercible($pristine)) {
			return self::invalid($pristine);
		}

		return new Coercion(self::toFloat($pristine), $pristine);
	}

	private static function invalid(mixed $pristine): Coercion
	{
		return new Coercion(
			$pristine,
			$pristine,
			Failure::invalid(),
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

	private static function toFloat(mixed $value): ?float
	{
		return is_null($value) ? null : (float) $value;
	}
}
