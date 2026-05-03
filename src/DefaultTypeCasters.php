<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

final class DefaultTypeCasters implements Contract\TypeCasterRegistry
{
	/** @var array<string, Contract\TypeCaster> */
	private array $loaded = [];

	/** @param array<string, string> $messages */
	public function __construct(
		private readonly array $messages,
	) {}

	#[Override]
	public function get(string $name): ?Contract\TypeCaster
	{
		if (array_key_exists($name, $this->loaded)) {
			return $this->loaded[$name];
		}

		$caster = match ($name) {
			'text' => new Caster\Text(),
			'bool' => new Caster\Boolean($this->messages),
			'list' => new Caster\Sequence($this->messages),
			'float' => new Caster\FloatingPoint($this->messages),
			'int' => new Caster\Integer($this->messages),
			default => null,
		};

		if ($caster !== null) {
			$this->loaded[$name] = $caster;
		}

		return $caster;
	}
}
