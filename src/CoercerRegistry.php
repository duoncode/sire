<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;

/** @api */
final class CoercerRegistry implements Contract\CoercerRegistry
{
	/** @param array<string, Contract\Coercer> $coercers */
	public function __construct(
		private array $coercers = [],
		private ?Contract\CoercerRegistry $fallback = null,
	) {}

	public static function withDefaults(): self
	{
		return new self([], new DefaultCoercers());
	}

	public function with(string $name, Contract\Coercer $coercer): self
	{
		$coercers = $this->coercers;
		$coercers[$name] = $coercer;

		return new self($coercers, $this->fallback);
	}

	/** @param array<string, Contract\Coercer> $coercers */
	public function withMany(array $coercers): self
	{
		$result = $this;

		foreach ($coercers as $name => $coercer) {
			$result = $result->with($name, $coercer);
		}

		return $result;
	}

	#[Override]
	public function get(string $name): ?Contract\Coercer
	{
		if (array_key_exists($name, $this->coercers)) {
			return $this->coercers[$name];
		}

		return $this->fallback?->get($name);
	}
}
