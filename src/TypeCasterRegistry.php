<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

/** @api */
final class TypeCasterRegistry implements Contract\TypeCasterRegistry
{
	/** @param array<string, Contract\TypeCaster> $casters */
	public function __construct(
		private array $casters = [],
		private ?Contract\TypeCasterRegistry $fallback = null,
	) {}

	public static function withDefaults(array $messages): self
	{
		return new self(DefaultTypeCasters::all($messages));
	}

	public function with(string $name, Contract\TypeCaster $caster): self
	{
		$casters = $this->casters;
		$casters[$name] = $caster;

		return new self($casters, $this->fallback);
	}

	/** @param array<string, Contract\TypeCaster> $casters */
	public function withMany(array $casters): self
	{
		$result = $this;

		foreach ($casters as $name => $caster) {
			$result = $result->with($name, $caster);
		}

		return $result;
	}

	#[Override]
	public function get(string $name): ?Contract\TypeCaster
	{
		if (array_key_exists($name, $this->casters)) {
			return $this->casters[$name];
		}

		return $this->fallback?->get($name);
	}
}
