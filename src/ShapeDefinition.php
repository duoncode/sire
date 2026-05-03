<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;

/** @internal */
final readonly class ShapeDefinition
{
	/**
	 * @param array<string, Rule> $rules
	 * @param array<string, Validator> $validators
	 * @param array<string, Contract\TypeCaster> $typeCasters
	 * @param list<Closure(ReviewContext): void> $reviewCallbacks
	 */
	public function __construct(
		public bool $list,
		public bool $keepUnknown,
		public ?string $title,
		public array $rules,
		public array $validators,
		public array $typeCasters,
		public Contract\ValidatorDefinitionParser $validatorDefinitionParser,
		public array $reviewCallbacks,
	) {}
}
