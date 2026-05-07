<?php

declare(strict_types=1);

namespace Duon\Sire\Coercer;

use Duon\Sire\Coercion;
use Duon\Sire\CoercionMode;
use Duon\Sire\Contract;
use Duon\Sire\Failure;
use Override;

/** @api */
final class Integer implements Contract\Coercer
{
	public string $message {
		get => '{label} must be a whole number';
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

		$value = self::toInteger($pristine);

		return new Coercion($value, $pristine, empty: $value === null);
	}

	private static function coerceStrict(mixed $pristine): Coercion
	{
		if ($pristine === null) {
			return new Coercion(null, null, empty: true);
		}

		return is_int($pristine)
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
		return $value === null || is_int($value) || self::isIntegerString($value);
	}

	private static function isIntegerString(mixed $value): bool
	{
		return preg_match('/^([0-9]|-[1-9]|-?[1-9][0-9]*)$/i', trim((string) $value)) === 1;
	}

	private static function isEmpty(mixed $value): bool
	{
		return $value === null || is_string($value) && trim($value) === '';
	}

	private static function toInteger(mixed $value): ?int
	{
		return $value === null ? null : (int) $value;
	}
}
