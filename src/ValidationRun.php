<?php

declare(strict_types=1);

namespace Duon\Sire;

use Duon\Sire\Contract\Value;
use ValueError;

/** @internal */
final class ValidationRun
{
	private ErrorBag $errors;

	public function __construct(
		private readonly ShapeDefinition $shape,
		private readonly array $data,
		private readonly int $level,
	) {
		$this->errors = new ErrorBag();
	}

	public function validate(): Result
	{
		$values = $this->readValues($this->data);
		$validatedValues = [];

		if ($this->shape->list) {
			foreach ($values as $listIndex => $subValues) {
				assert(is_int($listIndex), 'Expected list shape values to use integer indexes');
				/** @var array<string, Value> $subValues */
				$this->errors->seedListItem($listIndex);
				$validatedValues[] = $this->validateItem($subValues, $listIndex);
			}
		} else {
			/** @var array<string, Value> $values */
			$validatedValues = $this->validateItem($values);
		}

		$extractedValues = $this->extractValues($validatedValues);
		$pristineValues = $this->extractPristineValues($validatedValues);

		if (!$this->errors->hasErrors()) {
			$this->review($extractedValues, $pristineValues);
		}

		return new Result(
			$this->shape->list,
			$this->shape->title,
			$this->errors->map(),
			$this->errors->violations(),
			$extractedValues,
			$pristineValues,
		);
	}

	private static function isSkippableEmptyValue(mixed $value): bool
	{
		return $value === [] || $value === null || $value === false || $value === '';
	}

	private function validateField(
		Rule $rule,
		Value $value,
		string $validatorDefinition,
		?int $listIndex,
	): void {
		$parsedValidator = $this->shape->validatorParser->parse($validatorDefinition);
		$validatorName = $parsedValidator['name'];
		$validatorArgs = $parsedValidator['args'];

		$validator = $this->shape->validators->get($validatorName);

		if ($validator === null) {
			throw new ValueError(
				sprintf('Unknown validator "%s" in field "%s"', $validatorName, $rule->field),
			);
		}

		if ($validator->skipNull && self::isSkippableEmptyValue($value->value)) {
			return;
		}

		if (!$validator->validate($value, ...$validatorArgs)) {
			$this->errors->add(
				$rule->field,
				$rule->name(),
				sprintf(
					$validator->message,
					$rule->name(),
					$rule->field,
					print_r($value->pristine, true),
					...$validatorArgs,
				),
				$listIndex,
				$this->shape->title,
				$this->level,
			);
		}
	}

	/** @param array<string, Value>|list<array<string, Value>> $validatedValues */
	private function extractValues(array $validatedValues): array
	{
		if ($this->shape->list) {
			$values = [];

			foreach ($validatedValues as $item) {
				/** @var array<string, Value> $item */
				$values[] = $this->getValues($item);
			}

			return $values;
		}

		/** @var array<string, Value> $validatedValues */
		return $this->getValues($validatedValues);
	}

	/** @param array<string, Value>|list<array<string, Value>> $validatedValues */
	private function extractPristineValues(array $validatedValues): array
	{
		if ($this->shape->list) {
			$pristineValues = [];

			foreach ($validatedValues as $item) {
				/** @var array<string, Value> $item */
				$pristineValues[] = $this->getPristineValues($item);
			}

			return $pristineValues;
		}

		/** @var array<string, Value> $validatedValues */
		return $this->getPristineValues($validatedValues);
	}

	/** @return array<string, Value> */
	private function readFromData(array $data, ?int $listIndex = null): array
	{
		$values = [];

		foreach ($data as $field => $value) {
			$field = (string) $field;
			$rule = $this->shape->rules[$field] ?? null;

			if ($rule) {
				$valObj = $this->readKnownValue($rule, $value);

				if ($valObj->error !== null) {
					if ($rule->type() === 'shape') {
						assert(is_array($valObj->error), 'Expected nested shape errors to be arrays');
						$this->errors->addNested($field, $valObj->error, $listIndex);
					} else {
						if (!is_string($valObj->error)) {
							throw new ValueError('Wrong error type');
						}

						$this->errors->add(
							$field,
							$rule->name(),
							$valObj->error,
							$listIndex,
							$this->shape->title,
							$this->level,
						);
					}
				}

				$values[$field] = $valObj;
			} else {
				if ($this->shape->keepUnknown) {
					$values[$field] = new \Duon\Sire\Value($value, $value);
				}
			}
		}

		return $values;
	}

	private function readKnownValue(Rule $rule, mixed $value): Value
	{
		$value = $rule->applyPreparation($value);
		$type = $rule->type();

		if ($type === 'shape') {
			$shape = $rule->type;
			assert($shape instanceof Contract\Shape, 'Expected shape rule type to be a shape instance');

			return $this->toSubValues($value, $shape);
		}

		$coercer = $this->shape->coercers->get($type);

		if ($coercer === null) {
			throw new ValueError('Wrong shape type');
		}

		return $coercer->coerce($value, $rule->name());
	}

	private function toSubValues(mixed $pristine, Contract\Shape $shape): Value
	{
		$result = $shape->validate($pristine, $this->level + 1);

		if ($result->isValid()) {
			return new \Duon\Sire\Value($result->values(), $pristine);
		}

		return new \Duon\Sire\Value(
			$pristine,
			$pristine,
			[
				'errors' => $result->violations(),
				'map' => $result->map(),
			],
		);
	}

	/**
	 * @param array<string, mixed> $values
	 * @param array<string, mixed> $pristineValues
	 */
	private function review(array $values, array $pristineValues): void
	{
		$context = new Review(
			$this->errors,
			$values,
			$pristineValues,
			$this->shape->list,
			$this->shape->title,
			$this->level,
		);

		foreach ($this->shape->reviewCallbacks as $review) {
			$review($context);
		}
	}

	/** @param array<string, Value> $values */
	private function fillMissingFromRules(array $values): array
	{
		foreach ($this->shape->rules as $field => $rule) {
			if (array_key_exists($field, $values)) {
				continue;
			}

			if ($rule->type() === 'bool') {
				$values[$field] = new \Duon\Sire\Value(false, null);

				continue;
			}

			$values[$field] = new \Duon\Sire\Value(null, null);
		}

		return $values;
	}

	/** @return array<string, Value>|list<array<string, Value>> */
	private function readValues(array $data): array
	{
		if ($this->shape->list) {
			$values = [];

			foreach ($data as $listIndex => $item) {
				assert(is_int($listIndex), 'Expected list shape data to use integer indexes');
				/** @var array<string, mixed> $subData */
				$subData = $item;
				$subValues = $this->readFromData($subData, $listIndex);
				$values[] = $this->fillMissingFromRules($subValues);
			}

			return $values;
		}

		$values = $this->readFromData($data);

		return $this->fillMissingFromRules($values);
	}

	/**
	 * @param array<string, Value> $values
	 * @return array<string, Value>
	 */
	private function validateItem(array $values, ?int $listIndex = null): array
	{
		foreach ($this->shape->rules as $field => $rule) {
			foreach ($rule->validators as $validator) {
				$this->validateField(
					$rule,
					$values[$field],
					$validator,
					$listIndex,
				);
			}
		}

		return $values;
	}

	/**
	 * @param array<string, Value> $values
	 * @return array<string, mixed>
	 */
	private function getValues(array $values): array
	{
		return array_map(
			static fn(Value $item): mixed => $item->value,
			$values,
		);
	}

	/**
	 * @param array<string, Value> $values
	 * @return array<string, mixed>
	 */
	private function getPristineValues(array $values): array
	{
		return array_map(
			static fn(Value $item): mixed => $item->pristine,
			$values,
		);
	}
}
