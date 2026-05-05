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

	private ?Contract\ValidatorRegistry $validatorRegistry = null;

	/** @var array<string, Contract\Validator> */
	private array $validators = [];

	private ?Contract\CoercerRegistry $coercerRegistry = null;

	/** @var array<string, Contract\Coercer> */
	private array $coercers = [];

	private ?Contract\ValidatorParser $validatorParser = null;

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

	public function validator(string $name, Contract\Validator $validator): void
	{
		$this->validators[$name] = $validator;
	}

	public function validators(Contract\ValidatorRegistry $registry): void
	{
		$this->validatorRegistry = $registry;
		$this->validators = [];
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

	public function validatorParser(Contract\ValidatorParser $parser): void
	{
		$this->validatorParser = $parser;
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
			$this->resolvedValidatorRegistry(),
			$this->resolvedCoercerRegistry(),
			$this->resolvedValidatorParser(),
			$this->messageFormatter(),
			$reviewCallbacks,
		);
	}

	private function messageFormatter(): MessageFormatter
	{
		return new MessageFormatter($this->messages);
	}

	private function resolvedValidatorRegistry(): Contract\ValidatorRegistry
	{
		$this->validatorRegistry ??= ValidatorRegistry::withDefaults();

		if ($this->validators === []) {
			return $this->validatorRegistry;
		}

		return new ValidatorRegistry($this->validators, $this->validatorRegistry);
	}

	private function resolvedCoercerRegistry(): Contract\CoercerRegistry
	{
		$this->coercerRegistry ??= CoercerRegistry::withDefaults();

		if ($this->coercers === []) {
			return $this->coercerRegistry;
		}

		return new CoercerRegistry($this->coercers, $this->coercerRegistry);
	}

	private function resolvedValidatorParser(): Contract\ValidatorParser
	{
		return $this->validatorParser ??= new ValidatorParser();
	}
}
