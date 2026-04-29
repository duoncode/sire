<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

/** @api */
final class ValidatorRegistry implements Contract\ValidatorRegistry
{
	/** @param array<string, Validator> $validators */
	public function __construct(
		private array $validators = [],
	) {}

	public static function withDefaults(): self
	{
		return new self(DefaultValidators::all());
	}

	public function with(string $name, Validator $validator): self
	{
		$validators = $this->validators;
		$validators[$name] = $validator;

		return new self($validators);
	}

	/** @param array<string, Validator> $validators */
	public function withMany(array $validators): self
	{
		$result = $this;

		foreach ($validators as $name => $validator) {
			$result = $result->with($name, $validator);
		}

		return $result;
	}

	#[Override]
	/** @return array<string, Validator> */
	public function all(): array
	{
		return $this->validators;
	}
}
