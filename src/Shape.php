<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;
use ValueError;

/** @api */
class Shape implements Contract\Shape
{
	/** @var list<Violation> A list of validation violations */
	public array $errorList = [];
	protected array $validators = [];
	protected int $level = 0;
	/** @var array<string, Rule> */
	protected array $rules = [];
	protected array $errorMap = [];
	protected array $messages = [];
	/** @var array<string, Contract\TypeCaster> */
	protected array $typeCasters = [];
	protected Contract\ValidatorRegistry $validatorRegistry;
	protected Contract\ValidatorDefinitionParser $validatorDefinitionParser;
	protected Contract\TypeCasterRegistry $typeCasterRegistry;

	public function __construct(
		protected bool $list = false,
		protected bool $keepUnknown = false,
		protected array $langs = [],
		protected ?string $title = null,
		?Contract\ValidatorRegistry $validatorRegistry = null,
		?Contract\ValidatorDefinitionParser $validatorDefinitionParser = null,
		?Contract\TypeCasterRegistry $typeCasterRegistry = null,
	) {
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

		$rule = new Rule($field, $type, $validators);

		$this->rules[$field] = $rule;

		return $rule;
	}

	#[Override]
	public function validate(array $data, int $level = 1): ValidationResult
	{
		$this->level = $level;
		$this->errorList = [];
		$this->errorMap = [];

		$this->rules();

		$values = $this->readValues($data);
		$validatedValues = [];

		if ($this->list) {
			foreach ($values as $listIndex => $subValues) {
				// add an empty array for this item which will be
				// filled in case of error. Allows to show errors
				// next to the field in frontend (still TODO)
				if (!isset($this->errorMap[$listIndex])) {
					$this->errorMap[$listIndex] = [];
				}

				$validatedValues[] = $this->validateItem(
					$subValues,
					$listIndex,
				);
			}
		} else {
			$validatedValues = $this->validateItem($values);
		}

		if (count($this->errorList) === 0) {
			$this->review();
		}

		return new ValidationResult(
			$this->list,
			$this->title,
			$this->errorMap,
			$this->errorList,
			$this->extractValues($validatedValues),
			$this->extractPristineValues($validatedValues),
		);
	}

	/**
	 * This method is called before validation starts.
	 *
	 * It can be overwritten to add rules in a reusable shape
	 */
	protected function rules(): void
	{
		// Like:
		// $this->add('field', 'bool, 'required')->label('remember');
	}

	protected function addSubError(
		string $field,
		array $error,
		?int $listIndex,
	): void {
		foreach ($error['errors'] ?? [] as $err) {
			assert($err instanceof Violation);
			$this->errorList[] = $err;
		}

		$subErrorMap = is_array($error['map'] ?? null) ? $error['map'] : [];

		if ($listIndex === null) {
			$this->errorMap[$field] = $subErrorMap;
		} else {
			$this->errorMap[$listIndex][$field] = $subErrorMap;
		}
	}

	protected function addError(
		string $field,
		string $label,
		string $error,
		?int $listIndex = null,
	): void {
		$violation = new Violation(
			$error,
			$this->title,
			$this->level,
			$listIndex,
			$field,
			$label,
		);

		if ($listIndex === null) {
			if (!isset($this->errorMap[$field])) {
				$this->errorMap[$field] = [];
			}

			$this->errorMap[$field][] = $error;
		} else {
			if (!isset($this->errorMap[$listIndex][$field])) {
				$this->errorMap[$listIndex][$field] = [];
			}

			$this->errorMap[$listIndex][$field][] = $error;
		}

		$this->errorList[] = $violation;
	}

	protected function validateField(
		string $field,
		Value $value,
		string $validatorDefinition,
		?int $listIndex,
	): void {
		$parsedValidator = $this->validatorDefinitionParser->parse($validatorDefinition);
		$validatorName = $parsedValidator['name'];
		$validatorArgs = $parsedValidator['args'];

		if (!isset($this->validators[$validatorName])) {
			throw new ValueError(
				sprintf('Unknown validator "%s" in field "%s"', $validatorName, $field),
			);
		}

		$validator = $this->validators[$validatorName];

		if (is_array($value->value)) {
			if (empty($value->value) && $validator->skipNull) {
				return;
			}
		} else {
			if (empty($value->value) && strlen((string) $value->value) === 0 && $validator->skipNull) {
				return;
			}
		}

		if (!$validator->validate($value, ...$validatorArgs)) {
			$this->addError(
				$field,
				$this->rules[$field]->name(),
				sprintf(
					$validator->message,
					$this->rules[$field]->name(),
					$field,
					print_r($value->pristine, true),
					...$validatorArgs,
				),
				$listIndex,
			);
		}
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

	protected function extractValues(array $validatedValues): array
	{
		if ($this->list) {
			$values = [];

			foreach ($validatedValues as $item) {
				$values[] = $this->getValues($item);
			}

			return $values;
		}

		return $this->getValues($validatedValues);
	}

	protected function extractPristineValues(array $validatedValues): array
	{
		if ($this->list) {
			$pristineValues = [];

			foreach ($validatedValues as $item) {
				$pristineValues[] = $this->getPristineValues($item);
			}

			return $pristineValues;
		}

		return $this->getPristineValues($validatedValues);
	}

	protected function readFromData(array $data, ?int $listIndex = null): array
	{
		$values = [];

		foreach ($data as $field => $value) {
			$rule = $this->rules[$field] ?? null;

			if ($rule) {
				$label = $rule->name();
				$type = $rule->type();

				if ($type === 'shape') {
					$shape = $rule->type;
					assert($shape instanceof Contract\Shape);
					$valObj = $this->toSubValues($value, $shape);
				} else {
					$caster = $this->typeCasters[$type] ?? null;

					if ($caster === null) {
						throw new ValueError('Wrong shape type');
					}

					$valObj = $caster->cast($value, $label);
				}

				if ($valObj->error !== null) {
					if ($rule->type() === 'shape') {
						assert(is_array($valObj->error));
						$this->addSubError($field, $valObj->error, $listIndex);
					} else {
						if (!is_string($valObj->error)) {
							throw new ValueError('Wrong error type');
						}

						$this->addError(
							$field,
							$this->rules[$field]->name(),
							$valObj->error,
							$listIndex,
						);
					}
				}

				$values[$field] = $valObj;
			} else {
				if ($this->keepUnknown) {
					$values[$field] = new Value($value, $value);
				}
			}
		}

		return $values;
	}

	protected function fillMissingFromRules(array $values): array
	{
		foreach ($this->rules as $field => $rule) {
			if (!isset($values[$field])) {
				if ($rule->type() == 'bool') {
					$values[$field] = new Value(false, null);

					continue;
				}

				$values[$field] = new Value(null, null);
			}
		}

		return $values;
	}

	protected function readValues(array $data): array
	{
		if ($this->list) {
			$values = [];

			foreach ($data as $listIndex => $item) {
				$subValues = $this->readFromData($item, $listIndex);
				$values[] = $this->fillMissingFromRules($subValues);
			}

			return $values;
		}
		$values = $this->readFromData($data);

		return $this->fillMissingFromRules($values);
	}

	protected function validateItem(array $values, ?int $listIndex = null): array
	{
		foreach ($this->rules as $field => $rule) {
			foreach ($rule->validators as $validator) {
				$this->validateField(
					$field,
					$values[$field],
					$validator,
					$listIndex,
				);
			}
		}

		return $values;
	}

	protected function review(): void
	{
		// Can be overwritten in subclasses to make additional checks
		//
		// Implementations should call $this->addError('field_name', 'label', 'Error message');
		// in case of error.
	}

	protected function getValues(array $values): array
	{
		return array_map(
			function (Value $item): mixed {
				return $item->value;
			},
			$values,
		);
	}

	protected function getPristineValues(array $values): array
	{
		return array_map(
			function (Value $item): mixed {
				return $item->pristine;
			},
			$values,
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
}
