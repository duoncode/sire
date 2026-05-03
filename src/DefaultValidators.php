<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

final class DefaultValidators implements Contract\ValidatorRegistry
{
	/** @var array<string, Contract\Validator> */
	private array $loaded = [];

	#[Override]
	public function get(string $name): ?Contract\Validator
	{
		if (array_key_exists($name, $this->loaded)) {
			return $this->loaded[$name];
		}

		$validator = match ($name) {
			'required' => new Validator\Required(),
			'email' => new Validator\Email(),
			'minlen' => new Validator\MinLength(),
			'maxlen' => new Validator\MaxLength(),
			'min' => new Validator\Minimum(),
			'max' => new Validator\Maximum(),
			'regex' => new Validator\Regex(),
			'in' => new Validator\Allowed(),
			default => null,
		};

		if ($validator !== null) {
			$this->loaded[$name] = $validator;
		}

		return $validator;
	}
}
