<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @api */
final class Rule
{
	private ?string $label = null;

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

	public function name(): string
	{
		return $this->label ?? $this->field;
	}

	public function type(): string
	{
		return is_string($this->type) ? $this->type : 'shape';
	}
}
