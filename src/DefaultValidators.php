<?php

declare(strict_types=1);

namespace Duon\Sire;

final class DefaultValidators
{
	/** @return array<string, Validator> */
	public static function all(): array
	{
		return [
			'required' => new Validator(
				'required',
				'Required',
				function (Value $value, string ...$_args) {
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
			),
			'email' => new Validator(
				'email',
				'Invalid email address',
				function (Value $value, string ...$args) {
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
			),
			'minlen' => new Validator(
				'minlen',
				'Shorter than the minimum length of %4$s characters',
				function (Value $value, string ...$args) {
					return strlen($value->value) >= (int) $args[0];
				},
				true,
			),
			'maxlen' => new Validator(
				'maxlen',
				'Exeeds the maximum length of %4$s characters',
				function (Value $value, string ...$args) {
					return strlen($value->value) <= (int) $args[0];
				},
				true,
			),
			'min' => new Validator(
				'min',
				'Lower than the required minimum of %4$s',
				function (Value $value, string ...$args) {
					return (float) $value->value >= (float) $args[0];
				},
				true,
			),
			'max' => new Validator(
				'max',
				'Higher than the allowed maximum of %4$s',
				function (Value $value, string ...$args) {
					return $value->value <= (float) $args[0];
				},
				true,
			),
			'regex' => new Validator(
				'regex',
				'Does not match the required pattern',
				function (Value $value, string ...$args) {
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
			),
			'in' => new Validator(
				'in',
				'Invalid value',
				function (Value $value, string ...$args) {
					$allowed = DslSplitter::split($args[0] ?? '', ',');

					return in_array($value->value, $allowed, true);
				},
				true,
			),
		];
	}
}
