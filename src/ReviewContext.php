<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @api */
final readonly class ReviewContext
{
	public function __construct(
		private ErrorBag $errors,
		private array $values,
		private array $pristineValues,
		private bool $list,
		private ?string $title,
		private int $level,
	) {}

	public function addError(
		string $field,
		string $label,
		string $message,
		?int $listIndex = null,
	): void {
		$this->errors->add(
			$field,
			$label,
			$message,
			$listIndex,
			$this->title,
			$this->level,
		);
	}

	public function isList(): bool
	{
		return $this->list;
	}

	public function level(): int
	{
		return $this->level;
	}

	/** @return array<string, mixed> */
	public function pristineValues(): array
	{
		return $this->pristineValues;
	}

	public function title(): ?string
	{
		return $this->title;
	}

	/** @return array<string, mixed> */
	public function values(): array
	{
		return $this->values;
	}
}
