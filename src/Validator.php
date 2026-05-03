<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;
use Override;

/** @api */
final class Validator implements Contract\Validator
{
	public string $name;
	public string $message;
	public bool $skipEmpty;
	private Closure $validator;

	/** @param Closure(Contract\Value, string...): bool $validator */
	public function __construct(
		string $name,
		string $message,
		Closure $validator,
		bool $skipEmpty,
	) {
		$this->name = $name;
		$this->message = $message;
		$this->validator = $validator;
		$this->skipEmpty = $skipEmpty;
	}

	#[Override]
	public function validate(Contract\Value $value, string ...$args): bool
	{
		$func = $this->validator;

		return $func($value, ...$args);
	}
}
