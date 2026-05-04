<?php

declare(strict_types=1);

namespace Duon\Sire;

use Duon\Sire\Contract\Value;
use ValueError;

/** @internal */
final class ValidationRun
{
	private ErrorBag $errors;

	/** Marks defaulted fields so pristine output can preserve missing input. */
	private object $missing;

	public function __construct(
		private readonly ShapeDefinition $shape,
		private readonly array $data,
		private readonly int $level,
	) {
		$this->errors = new ErrorBag();
		// Use a unique per-run sentinel instead of null so explicit null input
		// remains distinguishable from fields filled by defaults.
		$this->missing = new \stdClass();
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

		if (!$this->errors->hasErrors()) {
			$validatedValues = $this->finalizeValues($validatedValues);
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

		if (
			!$validator instanceof Contract\ValidatesEmpty && self::isSkippableEmptyValue($value->value)
		) {
			return;
		}

		$validation = $validator->validate($value, ...$validatorArgs);

		if ($validation->failure !== null) {
			$this->errors->add(
				$rule->field,
				$rule->name(),
				$this->shape->messageFormatter->format(
					$validation->failure,
					$rule->name(),
					$rule->field,
					$value->pristine,
					'validator.' . $validatorName,
					$validator->message,
					$validatorArgs,
					$rule->messageOverrides(),
				),
				$listIndex,
				$this->shape->title,
				$this->level,
			);
		}
	}

	/** @param array<string, Value>|list<array<string, Value>> $validatedValues */
	private function finalizeValues(array $validatedValues): array
	{
		if ($this->shape->list) {
			$values = [];

			foreach ($validatedValues as $item) {
				/** @var array<string, Value> $item */
				$values[] = $this->finalizeItem($item);
			}

			return $values;
		}

		/** @var array<string, Value> $validatedValues */
		return $this->finalizeItem($validatedValues);
	}

	/**
	 * @param array<string, Value> $values
	 * @return array<string, Value>
	 */
	private function finalizeItem(array $values): array
	{
		$itemValues = $this->getValues($values);

		foreach ($this->shape->rules as $field => $rule) {
			if (!array_key_exists($field, $values)) {
				continue;
			}

			$value = $values[$field];
			$finalValue = $rule->applyFinalization($value->value, $itemValues);
			$values[$field] = new \Duon\Sire\Value($finalValue, $value->pristine);
		}

		return $values;
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
			$value = $rule instanceof Rule
				? $this->readRuleValue($field, $rule, $value, $data, $listIndex)
				: $this->readExtraValue($field, $value, $listIndex);

			if ($value !== null) {
				$values[$field] = $value;
			}
		}

		return $values;
	}

	/** @param array<string, mixed> $data */
	private function readRuleValue(
		string $field,
		Rule $rule,
		mixed $value,
		array $data,
		?int $listIndex,
	): ?Value {
		if ($rule->isBlank($value)) {
			return $this->readEmptyValue($field, $rule, $data, $listIndex);
		}

		$read = $this->readKnownValue($rule, $value, $data);

		if ($read->nestedError !== null) {
			$this->errors->addNested($field, $read->nestedError, $listIndex);
		}

		if ($read->error !== null) {
			$this->errors->add(
				$field,
				$rule->name(),
				$read->error,
				$listIndex,
				$this->shape->title,
				$this->level,
			);
		}

		return $read->value;
	}

	/** @param array<string, mixed> $data */
	private function readEmptyValue(
		string $field,
		Rule $rule,
		array $data,
		?int $listIndex,
	): ?Value {
		if ($rule->hasDefault()) {
			return $this->readDefaultValue($field, $rule, $data, $listIndex);
		}

		if ($rule->isOptional()) {
			return null;
		}

		$this->errors->add(
			$field,
			$rule->name(),
			$this->formatMissingFailure($rule),
			$listIndex,
			$this->shape->title,
			$this->level,
		);

		return null;
	}

	/** @param array<string, mixed> $data */
	private function readDefaultValue(
		string $field,
		Rule $rule,
		array $data,
		?int $listIndex,
	): Value {
		$read = $this->readKnownValue($rule, $rule->defaultValue(), $data);

		if ($read->nestedError !== null) {
			$this->errors->addNested($field, $read->nestedError, $listIndex);
		}

		if ($read->error !== null) {
			$this->errors->add(
				$field,
				$rule->name(),
				$read->error,
				$listIndex,
				$this->shape->title,
				$this->level,
			);
		}

		return new \Duon\Sire\Value($read->value->value, $this->missing);
	}

	private function readExtraValue(string $field, mixed $value, ?int $listIndex): ?Value
	{
		if ($this->shape->extra === Extra::Allow) {
			return new \Duon\Sire\Value($value, $value);
		}

		if ($this->shape->extra === Extra::Forbid) {
			$this->errors->add(
				$field,
				$field,
				$this->formatExtraFailure($field, $value),
				$listIndex,
				$this->shape->title,
				$this->level,
			);
		}

		return null;
	}

	/** @param array<string, mixed> $data */
	private function readKnownValue(Rule $rule, mixed $value, array $data): ReadValue
	{
		$value = $rule->applyPreparation($value, $data);

		if ($value === null) {
			return new ReadValue(
				new \Duon\Sire\Value(null, null),
				$rule->isNullable() ? null : $this->formatNullFailure($rule),
			);
		}

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

		$coercion = $coercer->coerce($value);
		$error = $this->formatCoercionFailure($coercion, $rule, $coercer);

		return new ReadValue(
			new \Duon\Sire\Value($coercion->value, $coercion->pristine),
			$error,
		);
	}

	private function formatExtraFailure(string $field, mixed $value): string
	{
		return $this->shape->messageFormatter->format(
			Failure::invalid(),
			$field,
			$field,
			$value,
			'extra',
			'Field "{field}" is not allowed',
		);
	}

	private function formatNullFailure(Rule $rule): string
	{
		return $this->shape->messageFormatter->format(
			Failure::key('null'),
			$rule->name(),
			$rule->field,
			null,
			'null',
			'{label} must not be null',
			messages: $rule->messageOverrides(),
		);
	}

	private function formatMissingFailure(Rule $rule): string
	{
		return $this->shape->messageFormatter->format(
			Failure::key('missing'),
			$rule->name(),
			$rule->field,
			null,
			'missing',
			'{label} is required',
			messages: $rule->messageOverrides(),
		);
	}

	private function formatCoercionFailure(
		Contract\Coercion $coercion,
		Rule $rule,
		Contract\Coercer $coercer,
	): ?string {
		if ($coercion->failure === null) {
			return null;
		}

		return $this->shape->messageFormatter->format(
			$coercion->failure,
			$rule->name(),
			$rule->field,
			$coercion->pristine,
			'type.' . $rule->type(),
			$coercer->message,
			messages: $rule->messageOverrides(),
		);
	}

	private function toSubValues(mixed $pristine, Contract\Shape $shape): ReadValue
	{
		$result = $shape->validate($pristine, $this->level + 1);

		if ($result->isValid()) {
			return new ReadValue(new \Duon\Sire\Value($result->values(), $pristine));
		}

		return new ReadValue(
			new \Duon\Sire\Value($pristine, $pristine),
			nestedError: [
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

	/**
	 * @param array<string, Value> $values
	 * @param array<string, mixed> $data
	 */
	private function fillMissingFromRules(array $values, array $data, ?int $listIndex = null): array
	{
		foreach ($this->shape->rules as $field => $rule) {
			if (array_key_exists($field, $values) || array_key_exists($field, $data)) {
				continue;
			}

			if ($rule->treatsMissingAsEmpty()) {
				$value = $this->readEmptyValue($field, $rule, $data, $listIndex);

				if ($value !== null) {
					$values[$field] = $value;
				}

				continue;
			}

			if ($rule->isOptional()) {
				continue;
			}

			$this->errors->add(
				$field,
				$rule->name(),
				$this->formatMissingFailure($rule),
				$listIndex,
				$this->shape->title,
				$this->level,
			);
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
				$values[] = $this->fillMissingFromRules($subValues, $subData, $listIndex);
			}

			return $values;
		}

		$values = $this->readFromData($data);

		return $this->fillMissingFromRules($values, $data);
	}

	/**
	 * @param array<string, Value> $values
	 * @return array<string, Value>
	 */
	private function validateItem(array $values, ?int $listIndex = null): array
	{
		foreach ($this->shape->rules as $field => $rule) {
			if (!array_key_exists($field, $values)) {
				continue;
			}

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
		$pristineValues = [];

		foreach ($values as $field => $value) {
			if ($value->pristine === $this->missing) {
				continue;
			}

			$pristineValues[$field] = $value->pristine;
		}

		return $pristineValues;
	}
}
