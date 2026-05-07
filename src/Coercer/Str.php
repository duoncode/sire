<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\CoercionMode;
use Duon\Sire\Contract;
use Duon\Sire\Failure;
use Override;
use Stringable;

/** @api */
final class Str implements Contract\Coercer
{
	public string $message {
		get => '{label} must be a string';
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

		$value = self::toString($pristine);

		return new Coercion($value, $pristine, empty: self::isEmpty($value));
	}

	private static function coerceStrict(mixed $pristine): Coercion
	{
		if ($pristine === null) {
			return new Coercion(null, null, empty: true);
		}

		return is_string($pristine)
			? new Coercion($pristine, $pristine, empty: self::isEmpty($pristine))
			: self::invalid($pristine);
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
			$value === null
			|| is_string($value)
			|| is_int($value)
			|| is_float($value)
			|| $value instanceof Stringable
		);
	}

	private static function toString(mixed $value): ?string
	{
		return $value === null ? null : (string) $value;
	}

	private static function isEmpty(?string $value): bool
	{
		return $value === null || $value === '';
	}
}
