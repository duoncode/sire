<?php

declare(strict_types=1);

namespace Duon\Sire;

final class DefaultTypeCasters
{
	/** @return array<string, Contract\TypeCaster> */
	public static function all(array $messages): array
	{
		return [
			'text' => self::text(),
			'bool' => self::bool($messages),
			'list' => self::list($messages),
			'float' => self::float($messages),
			'int' => self::int($messages),
		];
	}

	private static function text(): Contract\TypeCaster
	{
		return new TypeCaster(
			static function (mixed $pristine, string $_label): Value {
				if (self::isEmptyTextInput($pristine)) {
					return new Value(null, $pristine);
				}

				return new Value((string) $pristine, $pristine);
			},
		);
	}

	private static function bool(array $messages): Contract\TypeCaster
	{
		return new TypeCaster(
			static function (mixed $pristine, string $label) use ($messages): Value {
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
					sprintf($messages['bool'], $label),
				);
			},
		);
	}

	private static function list(array $messages): Contract\TypeCaster
	{
		return new TypeCaster(
			static function (mixed $pristine, string $label) use ($messages): Value {
				if (
					is_array($pristine)
					&& ($pristine === [] || array_keys($pristine) === range(0, count($pristine) - 1))
				) {
					return new Value($pristine, $pristine);
				}

				return new Value(
					$pristine,
					$pristine,
					sprintf($messages['list'], $label),
				);
			},
		);
	}

	private static function float(array $messages): Contract\TypeCaster
	{
		return new TypeCaster(
			static function (mixed $pristine, string $label) use ($messages): Value {
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
					sprintf($messages['float'], $label),
				);
			},
		);
	}

	private static function int(array $messages): Contract\TypeCaster
	{
		return new TypeCaster(
			static function (mixed $pristine, string $label) use ($messages): Value {
				if (is_int($pristine) || is_null($pristine)) {
					return new Value($pristine, $pristine);
				}

				if (preg_match('/^([0-9]|-[1-9]|-?[1-9][0-9]*)$/i', trim((string) $pristine))) {
					return new Value((int) $pristine, $pristine);
				}

				return new Value(
					$pristine,
					$pristine,
					sprintf($messages['int'], $label),
				);
			},
		);
	}

	private static function isEmptyTextInput(mixed $value): bool
	{
		return (
			$value === null
			|| $value === false
			|| $value === 0
			|| $value === 0.0
			|| $value === ''
			|| $value === '0'
			|| $value === []
		);
	}
}
