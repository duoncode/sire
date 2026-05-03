<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;

/** @internal */
final readonly class ShapeDefinition
{
	/**
	 * @param array<string, Rule> $rules
	 * @param list<Closure(Review): void> $reviewCallbacks
	 */
	public function __construct(
		public bool $list,
		public bool $keepUnknown,
		public ?string $title,
		public array $rules,
		public Contract\ValidatorRegistry $validators,
		public Contract\CoercerRegistry $coercers,
		public Contract\ValidatorParser $validatorParser,
		public array $reviewCallbacks,
	) {}
}
