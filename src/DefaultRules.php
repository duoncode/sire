<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

final class DefaultRules implements Contract\RuleRegistry
{
	/** @var array<string, Contract\Rule> */
	private array $loaded = [];

	#[Override]
	public function get(string $name): ?Contract\Rule
	{
		if (array_key_exists($name, $this->loaded)) {
			return $this->loaded[$name];
		}

		$rule = match ($name) {
			'required' => new Rule\Required(),
			'email' => new Rule\Email(),
			'minlen' => new Rule\MinLength(),
			'maxlen' => new Rule\MaxLength(),
			'min' => new Rule\Minimum(),
			'max' => new Rule\Maximum(),
			'regex' => new Rule\Regex(),
			'in' => new Rule\Allowed(),
			default => null,
		};

		if ($rule !== null) {
			$this->loaded[$name] = $rule;
		}

		return $rule;
	}
}
