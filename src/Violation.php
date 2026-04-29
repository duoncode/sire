<?php

declare(strict_types=1);

namespace Duon\Sire;

use JsonSerializable;
use Override;

/** @api */
final class Violation implements JsonSerializable
{
	public function __construct(
		public readonly string $error,
		public readonly ?string $title,
		public readonly int $level,
		public readonly ?int $item,
		public readonly string $field,
		public readonly string $label,
	) {}

	/** @param array{error: mixed, title: mixed, level: mixed, item: mixed, field: mixed, label: mixed} $data */
	public static function fromArray(array $data): self
	{
		return new self(
			(string) $data['error'],
			is_string($data['title']) ? $data['title'] : null,
			(int) $data['level'],
			is_int($data['item']) ? $data['item'] : null,
			(string) $data['field'],
			(string) $data['label'],
		);
	}

	/** @return array{error: string, title: ?string, level: int, item: ?int, field: string, label: string} */
	public function toArray(): array
	{
		return [
			'error' => $this->error,
			'title' => $this->title,
			'level' => $this->level,
			'item' => $this->item,
			'field' => $this->field,
			'label' => $this->label,
		];
	}

	/** @return array{error: string, title: ?string, level: int, item: ?int, field: string, label: string} */
	#[Override]
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
}
