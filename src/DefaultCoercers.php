<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

final class DefaultCoercers implements Contract\CoercerRegistry
{
	/** @var array<string, Contract\Coercer> */
	private array $loaded = [];

	#[Override]
	public function get(string $name): ?Contract\Coercer
	{
		if (array_key_exists($name, $this->loaded)) {
			return $this->loaded[$name];
		}

		$coercer = match ($name) {
			'text' => new Coercer\Text(),
			'bool' => new Coercer\Boolean(),
			'list' => new Coercer\Sequence(),
			'float' => new Coercer\FloatingPoint(),
			'int' => new Coercer\Integer(),
			default => null,
		};

		if ($coercer !== null) {
			$this->loaded[$name] = $coercer;
		}

		return $coercer;
	}
}
