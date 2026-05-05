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
	) {
		$this->errors = new ErrorBag();
	}

	public function validate(): Result
	{
		$values = $this->readValues($this->data);
		$validatedValues = [];

		if ($this->shape->list) {
			foreach ($values as $listIndex => $subValues) {
				/** @var array<string, Value> $subValues */
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

		if (!$this->errors->hasErrors()) {
			$this->review($extractedValues);
		}

		return new Result(
			$this->errors->issues(),
			$extractedValues,
		);
	}

	private static function isSkippableEmptyValue(mixed $value): bool
	{
		return $value === [] || $value === null || $value === false || $value === '';
	}

	private function validateField(
		Field $definition,
		Value $value,
		string $ruleDefinition,
		string|int|null $listIndex,
	): void {
		$parsedRule = $this->shape->ruleParser->parse($ruleDefinition);
		$ruleName = $parsedRule['name'];
		$ruleArgs = $parsedRule['args'];

		$rule = $this->shape->rules->get($ruleName);

		if ($rule === null) {
			throw new ValueError(
				sprintf('Unknown rule "%s" in field "%s"', $ruleName, $definition->field),
			);
		}

		if (!$rule instanceof Contract\ValidatesEmpty && self::isSkippableEmptyValue($value->value)) {
			return;
		}

		$validation = $rule->validate($value, ...$ruleArgs);

		if ($validation->failure !== null) {
			$this->errors->add(
				self::path($definition->field, $listIndex),
				$this->formatFailure(
					$validation->failure,
					$definition->name(),
					$definition->field,
					$value->pristine,
					'rule.' . $ruleName,
					$rule->message,
					$ruleArgs,
					$definition->messageOverrides(),
				),
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

		foreach ($this->shape->fields as $field => $definition) {
			if (!array_key_exists($field, $values)) {
				continue;
			}

			$value = $values[$field];
			$finalValue = $definition->applyFinalization($value->value, $itemValues);
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

	/** @return array<string, Value> */
	private function readFromData(array $data, string|int|null $listIndex = null): array
	{
		$values = [];

		foreach ($data as $field => $value) {
			$field = (string) $field;
			$definition = $this->shape->fields[$field] ?? null;
			$value = $definition instanceof Field
				? $this->readFieldValue($field, $definition, $value, $data, $listIndex)
				: $this->readExtraValue($field, $value, $listIndex);

			if ($value !== null) {
				$values[$field] = $value;
			}
		}

		return $values;
	}

	/** @param array<string, mixed> $data */
	private function readFieldValue(
		string $field,
		Field $definition,
		mixed $value,
		array $data,
		string|int|null $listIndex,
	): ?Value {
		if ($definition->isBlank($value)) {
			return $this->readEmptyValue($field, $definition, $data, $listIndex);
		}

		$read = $this->readKnownValue($definition, $value, $data);

		if ($read->nestedResult !== null) {
			$this->errors->addNested(self::path($field, $listIndex), $read->nestedResult);
		}

		if ($read->issue !== null) {
			$this->errors->add(self::path($field, $listIndex), $read->issue);
		}

		return $read->value;
	}

	/** @param array<string, mixed> $data */
	private function readEmptyValue(
		string $field,
		Field $definition,
		array $data,
		string|int|null $listIndex,
	): ?Value {
		if ($definition->hasDefault()) {
			return $this->readDefaultValue($field, $definition, $data, $listIndex);
		}

		if ($definition->isOptional()) {
			return null;
		}

		$this->errors->add(
			self::path($field, $listIndex),
			$this->formatMissingFailure($definition),
		);

		return null;
	}

	/** @param array<string, mixed> $data */
	private function readDefaultValue(
		string $field,
		Field $definition,
		array $data,
		string|int|null $listIndex,
	): Value {
		$read = $this->readKnownValue($definition, $definition->defaultValue(), $data);

		if ($read->nestedResult !== null) {
			$this->errors->addNested(self::path($field, $listIndex), $read->nestedResult);
		}

		if ($read->issue !== null) {
			$this->errors->add(self::path($field, $listIndex), $read->issue);
		}

		return $read->value;
	}

	private function readExtraValue(string $field, mixed $value, string|int|null $listIndex): ?Value
	{
		if ($this->shape->extra === Extra::Allow) {
			return new \Duon\Sire\Value($value, $value);
		}

		if ($this->shape->extra === Extra::Forbid) {
			$this->errors->add(
				self::path($field, $listIndex),
				$this->formatExtraFailure($field, $value),
			);
		}

		return null;
	}

	/** @param array<string, mixed> $data */
	private function readKnownValue(Field $definition, mixed $value, array $data): ReadValue
	{
		$value = $definition->applyPreparation($value, $data);

		if ($value === null) {
			return new ReadValue(
				new \Duon\Sire\Value(null, null),
				$definition->isNullable() ? null : $this->formatNullFailure($definition),
			);
		}

		$type = $definition->type();

		if ($type === 'shape') {
			$shape = $definition->type;
			assert($shape instanceof Contract\Validator, 'Expected shape field type to be a shape instance');

			return $this->toSubValues($value, $definition, $shape);
		}

		$coercer = $this->shape->coercers->get($type);

		if ($coercer === null) {
			throw new ValueError('Wrong shape type');
		}

		$coercion = $coercer->coerce($value);

		return new ReadValue(
			new \Duon\Sire\Value($coercion->value, $coercion->pristine),
			$this->formatCoercionFailure($coercion, $definition, $coercer),
		);
	}

	private function formatExtraFailure(string $field, mixed $value): Issue
	{
		return $this->formatFailure(
			Failure::invalid(),
			$field,
			$field,
			$value,
			'extra',
			'Field "{field}" is not allowed',
		);
	}

	private function formatNullFailure(Field $definition): Issue
	{
		return $this->formatFailure(
			Failure::key('null'),
			$definition->name(),
			$definition->field,
			null,
			'null',
			'{label} must not be null',
			messages: $definition->messageOverrides(),
		);
	}

	private function formatMissingFailure(Field $definition): Issue
	{
		return $this->formatFailure(
			Failure::key('missing'),
			$definition->name(),
			$definition->field,
			null,
			'missing',
			'{label} is required',
			messages: $definition->messageOverrides(),
		);
	}

	private function formatCoercionFailure(
		Contract\Coercion $coercion,
		Field $definition,
		Contract\Coercer $coercer,
	): ?Issue {
		if ($coercion->failure === null) {
			return null;
		}

		return $this->formatFailure(
			$coercion->failure,
			$definition->name(),
			$definition->field,
			$coercion->pristine,
			'type.' . $definition->type(),
			$coercer->message,
			messages: $definition->messageOverrides(),
		);
	}

	private function formatShapeFailure(Field $definition, mixed $value): Issue
	{
		return $this->formatFailure(
			Failure::invalid(),
			$definition->name(),
			$definition->field,
			$value,
			'type.shape',
			'{label} must be an array',
			messages: $definition->messageOverrides(),
		);
	}

	/**
	 * @param list<mixed> $args
	 * @param array<string, string> $messages
	 */
	private function formatFailure(
		Failure $failure,
		string $label,
		string $field,
		mixed $pristine,
		?string $defaultKey,
		string $fallback,
		array $args = [],
		array $messages = [],
	): Issue {
		$args = $failure->args === [] ? $args : $failure->args;

		return new Issue(
			[],
			$failure->key !== '' ? $failure->key : $defaultKey ?? 'invalid',
			$this->shape->messageFormatter->format(
				$failure,
				$label,
				$field,
				$pristine,
				$defaultKey,
				$fallback,
				$args,
				$messages,
			),
			self::params($args),
		);
	}

	private function toSubValues(
		mixed $pristine,
		Field $definition,
		Contract\Validator $shape,
	): ReadValue {
		if (!is_array($pristine)) {
			return new ReadValue(
				new \Duon\Sire\Value($pristine, $pristine),
				$this->formatShapeFailure($definition, $pristine),
			);
		}

		$result = $shape->validate($pristine);

		if ($result->valid()) {
			return new ReadValue(new \Duon\Sire\Value($result->values(), $pristine));
		}

		return new ReadValue(
			new \Duon\Sire\Value($pristine, $pristine),
			nestedResult: $result,
		);
	}

	/** @param array<string, mixed>|list<array<string, mixed>> $values */
	private function review(array $values): void
	{
		$context = new Review(
			$this->errors,
			$values,
			$this->shape->list,
		);

		foreach ($this->shape->reviewCallbacks as $review) {
			$review($context);
		}
	}

	/**
	 * @param array<string, Value> $values
	 * @param array<string, mixed> $data
	 * @return array<string, Value>
	 */
	private function fillMissingFromFields(
		array $values,
		array $data,
		string|int|null $listIndex = null,
	): array {
		foreach ($this->shape->fields as $field => $definition) {
			if (array_key_exists($field, $values) || array_key_exists($field, $data)) {
				continue;
			}

			if ($definition->treatsMissingAsEmpty()) {
				$value = $this->readEmptyValue($field, $definition, $data, $listIndex);

				if ($value !== null) {
					$values[$field] = $value;
				}

				continue;
			}

			if ($definition->isOptional()) {
				continue;
			}

			$this->errors->add(
				self::path($field, $listIndex),
				$this->formatMissingFailure($definition),
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
				if (!is_array($item)) {
					$this->errors->add(
						[$listIndex],
						new Issue([], 'type.shape', 'Item must be an array'),
					);
					$values[] = [];

					continue;
				}

				/** @var array<string, mixed> $item */
				$subValues = $this->readFromData($item, $listIndex);
				$values[] = $this->fillMissingFromFields($subValues, $item, $listIndex);
			}

			return $values;
		}

		$values = $this->readFromData($data);

		return $this->fillMissingFromFields($values, $data);
	}

	/**
	 * @param array<string, Value> $values
	 * @return array<string, Value>
	 */
	private function validateItem(array $values, string|int|null $listIndex = null): array
	{
		foreach ($this->shape->fields as $field => $definition) {
			if (!array_key_exists($field, $values)) {
				continue;
			}

			foreach ($definition->rules as $rule) {
				$this->validateField(
					$definition,
					$values[$field],
					$rule,
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

	/** @return list<string|int> */
	private static function path(string $field, string|int|null $listIndex): array
	{
		if ($listIndex === null) {
			return [$field];
		}

		return [$listIndex, $field];
	}

	/**
	 * @param list<mixed> $args
	 * @return array<string, mixed>
	 */
	private static function params(array $args): array
	{
		$params = [];

		foreach ($args as $index => $arg) {
			$params['arg' . ($index + 1)] = $arg;
		}

		return $params;
	}
}
