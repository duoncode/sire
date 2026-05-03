<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;
use Override;
use ValueError;

/** @api */
final class Shape implements Contract\Shape
{
	/** @var array<string, Validator> */
	private array $validators = [];

	/** @var array<string, Rule> */
	private array $rules = [];

	/** @var list<Closure(Review): void> */
	private array $reviewCallbacks = [];

	/** @var array<string, Contract\TypeCaster> */
	private array $typeCasters = [];

	private Contract\ValidatorRegistry $validatorRegistry;

	private Contract\ValidatorDefinitionParser $validatorDefinitionParser;

	private Contract\TypeCasterRegistry $typeCasterRegistry;

	public function __construct(
		private bool $list = false,
		private bool $keepUnknown = false,
		array $langs = [],
		private ?string $title = null,
		?Contract\ValidatorRegistry $validatorRegistry = null,
		?Contract\ValidatorDefinitionParser $validatorDefinitionParser = null,
		?Contract\TypeCasterRegistry $typeCasterRegistry = null,
	) {
		unset($langs);
		$messages = self::messages();
		$this->validatorRegistry = $validatorRegistry ?? ValidatorRegistry::withDefaults();
		$this->validatorDefinitionParser = $validatorDefinitionParser ?? new ValidatorDefinitionParser();
		$this->typeCasterRegistry = $typeCasterRegistry ?? TypeCasterRegistry::withDefaults($messages);
		$this->loadDefaultValidators();
		$this->loadDefaultTypeCasters();
	}

	public function add(
		string $field,
		string|Contract\Shape $type,
		string ...$validators,
	): Rule {
		if (!$field) {
			throw new ValueError(
				'Shape definition error: field must not be empty',
			);
		}

		/** @var list<string> $validatorList */
		$validatorList = $validators;
		$rule = new Rule($field, $type, $validatorList);

		$this->rules[$field] = $rule;

		return $rule;
	}

	/** @param Closure(Review): void $callback */
	public function review(Closure $callback): self
	{
		$this->reviewCallbacks[] = $callback;

		return $this;
	}

	#[Override]
	public function validate(array $data, int $level = 1): ValidationResult
	{
		return new ValidationRun(
			$this->definition(),
			$data,
			$level,
		)->validate();
	}

	private static function messages(): array
	{
		// You can use the following placeholder to get more
		// information into your error messages:
		//
		//     %1$s for the field label if set, otherwise the field name
		//     %2$s for the field name
		//     %3$s for the original value
		//     %4$s for the first validator parameter
		//     %5$s for the next validator parameter
		//     %6$s for the next validator and so on
		//
		//  e. g. 'int' => 'Invalid number "%3$1" in field "%1$s"'

		return [
			// Types:
			'bool' => 'Invalid boolean',
			'float' => 'Invalid number',
			'int' => 'Invalid number',
			'list' => 'Invalid list',
		];
	}

	private function loadDefaultValidators(): void
	{
		foreach ($this->validatorRegistry->all() as $name => $validator) {
			$this->validators[$name] = $validator;
		}
	}

	private function loadDefaultTypeCasters(): void
	{
		foreach ($this->typeCasterRegistry->all() as $name => $caster) {
			$this->typeCasters[$name] = $caster;
		}
	}

	private function definition(): ShapeDefinition
	{
		return new ShapeDefinition(
			$this->list,
			$this->keepUnknown,
			$this->title,
			$this->rules,
			$this->validators,
			$this->typeCasters,
			$this->validatorDefinitionParser,
			$this->reviewCallbacks,
		);
	}
}
