<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @internal */
final class ErrorBag
{
	/** @var list<Violation> */
	private array $violations = [];

	private array $map = [];

	public function add(
		string $field,
		string $label,
		string $error,
		?int $listIndex,
		?string $title,
		int $level,
	): void {
		$this->violations[] = new Violation(
			$error,
			$title,
			$level,
			$listIndex,
			$field,
			$label,
		);

		if ($listIndex === null) {
			if (!array_key_exists($field, $this->map)) {
				$this->map[$field] = [];
			}

			$this->map[$field][] = $error;

			return;
		}

		if (!array_key_exists($field, $this->map[$listIndex] ?? [])) {
			$this->map[$listIndex][$field] = [];
		}

		$this->map[$listIndex][$field][] = $error;
	}

	/** @param array{errors?: list<Violation>, map?: array} $error */
	public function addNested(string $field, array $error, ?int $listIndex): void
	{
		foreach ($error['errors'] ?? [] as $err) {
			$this->violations[] = $err;
		}

		$subErrorMap = $error['map'] ?? [];

		if ($listIndex === null) {
			$this->map[$field] = $subErrorMap;

			return;
		}

		$this->map[$listIndex][$field] = $subErrorMap;
	}

	public function seedListItem(int $listIndex): void
	{
		if (!array_key_exists($listIndex, $this->map)) {
			$this->map[$listIndex] = [];
		}
	}

	public function hasErrors(): bool
	{
		return count($this->violations) > 0;
	}

	public function map(): array
	{
		return $this->map;
	}

	/** @return list<Violation> */
	public function violations(): array
	{
		return $this->violations;
	}
}
