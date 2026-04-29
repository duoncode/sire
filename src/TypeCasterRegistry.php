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
	) {}

	public static function withDefaults(array $messages): self
	{
		return new self(DefaultTypeCasters::all($messages));
	}

	public function with(string $name, Contract\TypeCaster $caster): self
	{
		$casters = $this->casters;
		$casters[$name] = $caster;

		return new self($casters);
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
	/** @return array<string, Contract\TypeCaster> */
	public function all(): array
	{
		return $this->casters;
	}
}
