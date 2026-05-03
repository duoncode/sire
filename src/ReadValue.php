<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @internal */
final readonly class ReadValue
{
	/** @param array{errors?: list<Violation>, map?: array}|null $nestedError */
	public function __construct(
		public Contract\Value $value,
		public ?string $error = null,
		public ?array $nestedError = null,
	) {}
}
