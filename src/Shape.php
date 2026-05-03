<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;
use Override;
use ValueError;

/** @api */
final class Shape implements Contract\Shape
{
	private Config $config;

	/** @var array<string, Rule> */
	private array $rules = [];

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

	public function keepUnknown(bool $keep = true): self
	{
		$this->config->keepUnknown($keep);

		return $this;
	}

	public function title(?string $title): self
	{
		$this->config->title($title);

		return $this;
	}

	public function validator(string $name, Validator $validator): self
	{
		$this->config->validator($name, $validator);

		return $this;
	}

	public function validators(Contract\ValidatorRegistry $registry): self
	{
		$this->config->validators($registry);

		return $this;
	}

	public function type(string $name, Contract\TypeCaster $caster): self
	{
		$this->config->type($name, $caster);

		return $this;
	}

	public function types(Contract\TypeCasterRegistry $registry): self
	{
		$this->config->types($registry);

		return $this;
	}

	public function validatorParser(Contract\ValidatorParser $parser): self
	{
		$this->config->validatorParser($parser);

		return $this;
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
	public function validate(array $data, int $level = 1): Result
	{
		return new ValidationRun(
			$this->definition(),
			$data,
			$level,
		)->validate();
	}

	private function definition(): ShapeDefinition
	{
		return $this->config->definition($this->rules, $this->reviewCallbacks);
	}
}
