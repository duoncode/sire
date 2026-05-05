<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

/** @api */
final class RuleRegistry implements Contract\RuleRegistry
{
	/** @param array<string, Contract\Rule> $rules */
	public function __construct(
		private array $rules = [],
		private ?Contract\RuleRegistry $fallback = null,
	) {}

	public static function withDefaults(): self
	{
		return new self([], new DefaultRules());
	}

	public function with(string $name, Contract\Rule $rule): self
	{
		$rules = $this->rules;
		$rules[$name] = $rule;

		return new self($rules, $this->fallback);
	}

	/** @param array<string, Contract\Rule> $rules */
	public function withMany(array $rules): self
	{
		$result = $this;

		foreach ($rules as $name => $rule) {
			$result = $result->with($name, $rule);
		}

		return $result;
	}

	#[Override]
	public function get(string $name): ?Contract\Rule
	{
		if (array_key_exists($name, $this->rules)) {
			return $this->rules[$name];
		}

		return $this->fallback?->get($name);
	}
}
