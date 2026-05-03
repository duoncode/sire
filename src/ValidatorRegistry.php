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
		private ?Contract\ValidatorRegistry $fallback = null,
	) {}

	public static function withDefaults(): self
	{
		return new self([], new DefaultValidators());
	}

	public function with(string $name, Validator $validator): self
	{
		$validators = $this->validators;
		$validators[$name] = $validator;

		return new self($validators, $this->fallback);
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
	public function get(string $name): ?Validator
	{
		if (array_key_exists($name, $this->validators)) {
			return $this->validators[$name];
		}

		return $this->fallback?->get($name);
	}
}
