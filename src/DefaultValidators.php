<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

final class DefaultValidators implements Contract\ValidatorRegistry
{
	/** @var array<string, Validator> */
	private array $loaded = [];

	#[Override]
	public function get(string $name): ?Validator
	{
		if (array_key_exists($name, $this->loaded)) {
			return $this->loaded[$name];
		}

		$validator = match ($name) {
			'required' => self::required(),
			'email' => self::email(),
			'minlen' => self::minlen(),
			'maxlen' => self::maxlen(),
			'min' => self::min(),
			'max' => self::max(),
			'regex' => self::regex(),
			'in' => self::in(),
			default => null,
		};

		if ($validator !== null) {
			$this->loaded[$name] = $validator;
		}

		return $validator;
	}

	private static function required(): Validator
	{
		return new Validator(
			'required',
			'Required',
			static function (Value $value, string ...$_args) {
				$val = $value->value;

				if (is_null($val)) {
					return false;
				}

				if (is_array($val) && count($val) === 0) {
					return false;
				}

				return true;
			},
			false,
		);
	}

	private static function email(): Validator
	{
		return new Validator(
			'email',
			'Invalid email address',
			static function (Value $value, string ...$args) {
				$email = filter_var(
					trim((string) $value->value),
					\FILTER_VALIDATE_EMAIL,
				);

				if ($email !== false && ($args[0] ?? null) === 'checkdns') {
					[, $mailDomain] = explode('@', $email);

					return checkdnsrr($mailDomain, 'MX');
				}

				return $email !== false;
			},
			true,
		);
	}

	private static function minlen(): Validator
	{
		return new Validator(
			'minlen',
			'Shorter than the minimum length of %4$s characters',
			static fn(Value $value, string ...$args) => strlen($value->value) >= (int) $args[0],
			true,
		);
	}

	private static function maxlen(): Validator
	{
		return new Validator(
			'maxlen',
			'Exeeds the maximum length of %4$s characters',
			static fn(Value $value, string ...$args) => strlen($value->value) <= (int) $args[0],
			true,
		);
	}

	private static function min(): Validator
	{
		return new Validator(
			'min',
			'Lower than the required minimum of %4$s',
			static fn(Value $value, string ...$args) => (float) $value->value >= (float) $args[0],
			true,
		);
	}

	private static function max(): Validator
	{
		return new Validator(
			'max',
			'Higher than the allowed maximum of %4$s',
			static fn(Value $value, string ...$args) => $value->value <= (float) $args[0],
			true,
		);
	}

	private static function regex(): Validator
	{
		return new Validator(
			'regex',
			'Does not match the required pattern',
			static function (Value $value, string ...$args) {
				// As regex patterns could contain colons ':' and validator
				// args are separated by colons and split at their position
				// we need to join them again
				$pattern = implode(':', $args);

				if ($pattern === '') {
					return false;
				}

				return preg_match($pattern, $value->value) === 1;
			},
			true,
		);
	}

	private static function in(): Validator
	{
		return new Validator(
			'in',
			'Invalid value',
			static function (Value $value, string ...$args) {
				$allowed = DslSplitter::split($args[0] ?? '', ',');

				return in_array($value->value, $allowed, true);
			},
			true,
		);
	}
}
