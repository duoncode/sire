<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;
use Override;
use ValueError;

/** @api */
class Shape implements Contract\Shape
{
	/** @var array<string, Validator> */
	protected array $validators = [];

	protected int $level = 0;

	/** @var array<string, Rule> */
	protected array $rules = [];

	protected array $messages = [];

	/** @var list<Closure(ReviewContext): void> */
	private array $reviewCallbacks = [];

	/** @var array<string, Contract\TypeCaster> */
	protected array $typeCasters = [];

	protected Contract\ValidatorRegistry $validatorRegistry;

	protected Contract\ValidatorDefinitionParser $validatorDefinitionParser;

	protected Contract\TypeCasterRegistry $typeCasterRegistry;

	public function __construct(
		protected bool $list = false,
		protected bool $keepUnknown = false,
		array $langs = [],
		protected ?string $title = null,
		?Contract\ValidatorRegistry $validatorRegistry = null,
		?Contract\ValidatorDefinitionParser $validatorDefinitionParser = null,
		?Contract\TypeCasterRegistry $typeCasterRegistry = null,
	) {
		unset($langs);
		$this->loadMessages();
		$this->validatorRegistry = $validatorRegistry ?? ValidatorRegistry::withDefaults();
		$this->validatorDefinitionParser = $validatorDefinitionParser ?? new ValidatorDefinitionParser();
		$this->typeCasterRegistry =
			$typeCasterRegistry ?? TypeCasterRegistry::withDefaults($this->messages);
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

	/** @param Closure(ReviewContext): void $callback */
	public function review(Closure $callback): self
	{
		$this->reviewCallbacks[] = $callback;

		return $this;
	}

	#[Override]
	public function validate(array $data, int $level = 1): ValidationResult
	{
		$this->level = $level;
		$this->rules();

		return new ValidationRun(
			$this->definition(),
			$data,
			$level,
			$this->toSubValues(...),
		)->validate();
	}

	/**
	 * This method is called before validation starts.
	 *
	 * It can be overwritten to add rules in a reusable shape.
	 */
	protected function rules(): void
	{
		// Like:
		// $this->add('field', 'bool, 'required')->label('remember');
	}

	protected function toSubValues(mixed $pristine, Contract\Shape $shape): Value
	{
		$result = $shape->validate($pristine, $this->level + 1);

		if ($result->isValid()) {
			return new Value($result->values(), $pristine);
		}

		return new Value(
			$pristine,
			$pristine,
			[
				'errors' => $result->violations(),
				'map' => $result->map(),
			],
		);
	}

	protected function loadMessages(): void
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

		$this->messages = [
			// Types:
			'bool' => 'Invalid boolean',
			'float' => 'Invalid number',
			'int' => 'Invalid number',
			'list' => 'Invalid list',
		];
	}

	protected function loadDefaultValidators(): void
	{
		foreach ($this->validatorRegistry->all() as $name => $validator) {
			$this->validators[$name] = $validator;
		}
	}

	protected function loadDefaultTypeCasters(): void
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
