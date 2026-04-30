<?php

declare(strict_types=1);

namespace Duon\Sire;

use JsonSerializable;
use Override;

/** @api */
final class ValidationResult implements JsonSerializable
{
	/**
	 * @param list<Violation> $violations
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $pristineValues
	 */
	public function __construct(
		public readonly bool $isList,
		public readonly ?string $title,
		private array $map,
		private array $violations,
		private array $values,
		private array $pristineValues,
	) {}

	public function isValid(): bool
	{
		return count($this->violations) === 0;
	}

	/** @return list<Violation> */
	public function violations(): array
	{
		return $this->violations;
	}

	public function map(): array
	{
		return $this->map;
	}

	/** @return array<string, mixed> */
	public function values(): array
	{
		return $this->values;
	}

	/** @return array<string, mixed> */
	public function pristineValues(): array
	{
		return $this->pristineValues;
	}

	public function errors(bool $grouped = false): array
	{
		$result = [
			'isList' => $this->isList,
			'title' => $this->title,
			'map' => $this->map,
			'grouped' => $grouped,
		];

		if ($grouped) {
			$result['errors'] = $this->groupedErrors();

			return $result;
		}

		$result['errors'] = array_map(
			static fn(Violation $violation): array => $violation->toArray(),
			$this->violations,
		);

		return $result;
	}

	/** @return array{isValid: bool, isList: bool, title: ?string, map: array, violations: list<Violation>, values: array<string, mixed>, pristineValues: array<string, mixed>} */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'isValid' => $this->isValid(),
			'isList' => $this->isList,
			'title' => $this->title,
			'map' => $this->map,
			'violations' => $this->violations,
			'values' => $this->values,
			'pristineValues' => $this->pristineValues,
		];
	}

	/** @return list<array{title: ?string, errors: array<int, array{error: string, title: ?string, level: int, item: ?int, field: string, label: string}>}> */
	private function groupedErrors(): array
	{
		$errors = array_map(
			static fn(Violation $violation): array => $violation->toArray(),
			$this->violations,
		);

		$sections = [];

		foreach ($errors as $error) {
			$item = ['title' => $error['title'], 'level' => (string) $error['level']];

			if (in_array($item, $sections, true)) {
				continue;
			}

			$sections[] = $item;
		}

		usort($sections, static function ($a, $b): int {
			$aa = $a['level'] . $a['title'];
			$bb = $b['level'] . $b['title'];

			return $aa > $bb ? 1 : -1;
		});

		/** @var array<string, array<int, array{error: string, title: ?string, level: int, item: ?int, field: string, label: string}>> $groups */
		$groups = [];

		foreach ($errors as $error) {
			$groups[$this->titleKey($error['title'])][] = $error;
		}

		$result = [];

		foreach ($sections as $section) {
			$result[] = [
				'title' => $section['title'],
				'errors' => $groups[$this->titleKey($section['title'])],
			];
		}

		return $result;
	}

	private function titleKey(?string $title): string
	{
		return $title ?? '__root__';
	}
}
