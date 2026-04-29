<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;

/** @api */
class Validator
{
	public string $name;
	public string $message;
	public bool $skipNull;
	protected Closure $validator;

	public function __construct(
		string $name,
		string $message,
		Closure $validator,
		bool $skipNull,
	) {
		$this->name = $name;
		$this->message = $message;
		$this->validator = $validator;
		$this->skipNull = $skipNull;
	}

	public function validate(Value $value, string ...$args): bool
	{
		$func = $this->validator;

		return $func($value, ...$args);
	}
}
