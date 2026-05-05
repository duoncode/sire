<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;
use ValueError;

/** @internal */
final class Config
{
	private bool $list = false;

	private Extra $extra = Extra::Ignore;

	/** @var array<string, string> */
	private array $messages = [];

	private ?Contract\RuleRegistry $ruleRegistry = null;

	/** @var array<string, Contract\Rule> */
	private array $rules = [];

	private ?Contract\CoercerRegistry $coercerRegistry = null;

	/** @var array<string, Contract\Coercer> */
	private array $coercers = [];

	private ?Contract\RuleParser $ruleParser = null;

	public function asList(bool $list = true): void
	{
		$this->list = $list;
	}

	public function extra(Extra|string $extra): void
	{
		if ($extra instanceof Extra) {
			$this->extra = $extra;

			return;
		}

		$this->extra = Extra::tryFrom($extra) ?? throw new ValueError(sprintf(
			'Invalid extra mode "%s"',
			$extra,
		));
	}

	public function rule(string $name, Contract\Rule $rule): void
	{
		$this->rules[$name] = $rule;
	}

	public function rules(Contract\RuleRegistry $registry): void
	{
		$this->ruleRegistry = $registry;
		$this->rules = [];
	}

	public function coercer(string $name, Contract\Coercer $coercer): void
	{
		$this->coercers[$name] = $coercer;
	}

	public function coercers(Contract\CoercerRegistry $registry): void
	{
		$this->coercerRegistry = $registry;
		$this->coercers = [];
	}

	public function message(string $key, string $message): void
	{
		$this->messages[$key] = $message;
	}

	/** @param array<string, string> $messages */
	public function messages(array $messages): void
	{
		$this->messages = array_replace($this->messages, $messages);
	}

	public function ruleParser(Contract\RuleParser $parser): void
	{
		$this->ruleParser = $parser;
	}

	/**
	 * @param array<string, Field> $fields
	 * @param list<Closure(Review): void> $reviewCallbacks
	 */
	public function definition(array $fields, array $reviewCallbacks): ShapeDefinition
	{
		return new ShapeDefinition(
			$this->list,
			$this->extra,
			$fields,
			$this->resolvedRuleRegistry(),
			$this->resolvedCoercerRegistry(),
			$this->resolvedRuleParser(),
			$this->messageFormatter(),
			$reviewCallbacks,
		);
	}

	private function messageFormatter(): MessageFormatter
	{
		return new MessageFormatter($this->messages);
	}

	private function resolvedRuleRegistry(): Contract\RuleRegistry
	{
		$this->ruleRegistry ??= RuleRegistry::withDefaults();

		if ($this->rules === []) {
			return $this->ruleRegistry;
		}

		return new RuleRegistry($this->rules, $this->ruleRegistry);
	}

	private function resolvedCoercerRegistry(): Contract\CoercerRegistry
	{
		$this->coercerRegistry ??= CoercerRegistry::withDefaults();

		if ($this->coercers === []) {
			return $this->coercerRegistry;
		}

		return new CoercerRegistry($this->coercers, $this->coercerRegistry);
	}

	private function resolvedRuleParser(): Contract\RuleParser
	{
		return $this->ruleParser ??= new RuleParser();
	}
}
