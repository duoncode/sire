<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

final class DefaultTypeCasters implements Contract\TypeCasterRegistry
{
	/** @var array<string, Contract\TypeCaster> */
	private array $loaded = [];

	/** @param array<string, string> $messages */
	public function __construct(
		private readonly array $messages,
	) {}

	#[Override]
	public function get(string $name): ?Contract\TypeCaster
	{
		if (array_key_exists($name, $this->loaded)) {
			return $this->loaded[$name];
		}

		$caster = match ($name) {
			'text' => self::text(),
			'bool' => $this->bool(),
			'list' => $this->list(),
			'float' => $this->float(),
			'int' => $this->int(),
			default => null,
		};

		if ($caster !== null) {
			$this->loaded[$name] = $caster;
		}

		return $caster;
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

	private function bool(): Contract\TypeCaster
	{
		$messages = $this->messages;

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

	private function list(): Contract\TypeCaster
	{
		$messages = $this->messages;

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

	private function float(): Contract\TypeCaster
	{
		$messages = $this->messages;

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

	private function int(): Contract\TypeCaster
	{
		$messages = $this->messages;

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
