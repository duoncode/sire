<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @internal */
final readonly class ShapeDefinition
{
	/**
	 * @param array<string, Rule> $rules
	 * @param array<string, Validator> $validators
	 * @param array<string, Contract\TypeCaster> $typeCasters
	 */
	public function __construct(
		public bool $list,
		public bool $keepUnknown,
		public ?string $title,
		public array $rules,
		public array $validators,
		public array $typeCasters,
		public Contract\ValidatorDefinitionParser $validatorDefinitionParser,
	) {}
}
