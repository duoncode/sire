<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;

/** @api */
final class Rule
{
	private ?string $label = null;

	/** @var list<Closure(mixed): mixed> */
	private array $preparers = [];

	/** @param list<string> $validators */
	public function __construct(
		public readonly string $field,
		public readonly string|Contract\Shape $type,
		public readonly array $validators,
	) {}

	public function label(string $label): static
	{
		$this->label = $label;

		return $this;
	}

	/** @param Closure(mixed): mixed $callback */
	public function prepare(Closure $callback): static
	{
		$this->preparers[] = $callback;

		return $this;
	}

	public function name(): string
	{
		return $this->label ?? $this->field;
	}

	public function type(): string
	{
		return is_string($this->type) ? $this->type : 'shape';
	}

	public function applyPreparation(mixed $value): mixed
	{
		foreach ($this->preparers as $prepare) {
			$value = $prepare($value);
		}

		return $value;
	}
}
