<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

final class DefaultCoercers implements Contract\CoercerRegistry
{
	/** @var array<string, Contract\Coercer> */
	private array $loaded = [];

	/** @param array<string, string> $messages */
	public function __construct(
		private readonly array $messages,
	) {}

	#[Override]
	public function get(string $name): ?Contract\Coercer
	{
		if (array_key_exists($name, $this->loaded)) {
			return $this->loaded[$name];
		}

		$coercer = match ($name) {
			'text' => new Coercer\Text(),
			'bool' => new Coercer\Boolean($this->messages),
			'list' => new Coercer\Sequence($this->messages),
			'float' => new Coercer\FloatingPoint($this->messages),
			'int' => new Coercer\Integer($this->messages),
			default => null,
		};

		if ($coercer !== null) {
			$this->loaded[$name] = $coercer;
		}

		return $coercer;
	}
}
