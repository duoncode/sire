<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;
use Duon\Sire\Exception\ValidationError;
use Override;
use ValueError;

/** @api */
final class Shape implements Contract\Shape
{
	private Config $config;

	/** @var array<string, Field> */
	private array $fields = [];

	/** @var list<Closure(Review): void> */
	private array $reviewCallbacks = [];

	public function __construct()
	{
		$this->config = new Config();
	}

	public static function list(): self
	{
		return new self()->asList();
	}

	public function asList(bool $list = true): self
	{
		$this->config->asList($list);

		return $this;
	}

	public function extra(Extra|string $extra): self
	{
		$this->config->extra($extra);

		return $this;
	}

	public function rule(string $name, Contract\Rule $rule): self
	{
		$this->config->rule($name, $rule);

		return $this;
	}

	public function rules(Contract\RuleRegistry $registry): self
	{
		$this->config->rules($registry);

		return $this;
	}

	public function type(string $name, Contract\Coercer $coercer): self
	{
		$this->config->coercer($name, $coercer);

		return $this;
	}

	public function message(string $key, string $message): self
	{
		$this->config->message($key, $message);

		return $this;
	}

	/** @param array<string, string> $messages */
	public function messages(array $messages): self
	{
		$this->config->messages($messages);

		return $this;
	}

	public function types(Contract\CoercerRegistry $registry): self
	{
		$this->config->coercers($registry);

		return $this;
	}

	public function ruleParser(Contract\RuleParser $parser): self
	{
		$this->config->ruleParser($parser);

		return $this;
	}

	public function add(
		string $field,
		string|Contract\Validator $type,
		string ...$rules,
	): Field {
		if (!$field) {
			throw new ValueError(
				'Shape definition error: field must not be empty',
			);
		}

		/** @var list<string> $ruleList */
		$ruleList = $rules;
		$definition = new Field($field, $type, $ruleList);

		$this->fields[$field] = $definition;

		return $definition;
	}

	/** @param Closure(Review): void $callback */
	public function review(Closure $callback): self
	{
		$this->reviewCallbacks[] = $callback;

		return $this;
	}

	#[Override]
	public function validate(array $data): Result
	{
		return new ValidationRun(
			$this->definition(),
			$data,
		)->validate();
	}

	/**
	 * @return array<array-key, mixed>
	 * @throws ValidationError
	 */
	#[Override]
	public function parse(array $data): array
	{
		$result = $this->validate($data);

		if (!$result->valid()) {
			throw new ValidationError($result);
		}

		return $result->values();
	}

	private function definition(): ShapeDefinition
	{
		return $this->config->definition($this->fields, $this->reviewCallbacks);
	}
}
