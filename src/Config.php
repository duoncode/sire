<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;

/** @internal */
final class Config
{
	private bool $list = false;

	private bool $keepUnknown = false;

	private ?string $title = null;

	/** @var array<string, string> */
	private array $messages = [];

	private ?Contract\ValidatorRegistry $validatorRegistry = null;

	/** @var array<string, Validator> */
	private array $validators = [];

	private ?Contract\TypeCasterRegistry $typeCasterRegistry = null;

	/** @var array<string, Contract\TypeCaster> */
	private array $typeCasters = [];

	private ?Contract\ValidatorParser $validatorParser = null;

	public function asList(bool $list = true): void
	{
		$this->list = $list;
	}

	public function keepUnknown(bool $keep = true): void
	{
		$this->keepUnknown = $keep;
	}

	public function title(?string $title): void
	{
		$this->title = $title;
	}

	public function validator(string $name, Validator $validator): void
	{
		$this->validators[$name] = $validator;
	}

	public function validators(Contract\ValidatorRegistry $registry): void
	{
		$this->validatorRegistry = $registry;
		$this->validators = [];
	}

	public function type(string $name, Contract\TypeCaster $caster): void
	{
		$this->typeCasters[$name] = $caster;
	}

	public function types(Contract\TypeCasterRegistry $registry): void
	{
		$this->typeCasterRegistry = $registry;
		$this->typeCasters = [];
	}

	public function validatorParser(Contract\ValidatorParser $parser): void
	{
		$this->validatorParser = $parser;
	}

	/**
	 * @param array<string, Rule> $rules
	 * @param list<Closure(Review): void> $reviewCallbacks
	 */
	public function definition(array $rules, array $reviewCallbacks): ShapeDefinition
	{
		return new ShapeDefinition(
			$this->list,
			$this->keepUnknown,
			$this->title,
			$rules,
			$this->loadedValidatorRegistry(),
			$this->loadedTypeCasterRegistry(),
			$this->loadedValidatorParser(),
			$reviewCallbacks,
		);
	}

	/** @return array<string, string> */
	private static function defaultMessages(): array
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

	/** @return array<string, string> */
	private function messages(): array
	{
		return array_replace(self::defaultMessages(), $this->messages);
	}

	private function loadedValidatorRegistry(): Contract\ValidatorRegistry
	{
		$registry = $this->baseValidatorRegistry();

		if ($this->validators === []) {
			return $registry;
		}

		return new ValidatorRegistry($this->validators, $registry);
	}

	private function baseValidatorRegistry(): Contract\ValidatorRegistry
	{
		if ($this->validatorRegistry === null) {
			$this->validatorRegistry = ValidatorRegistry::withDefaults();
		}

		return $this->validatorRegistry;
	}

	private function loadedTypeCasterRegistry(): Contract\TypeCasterRegistry
	{
		$registry = $this->baseTypeCasterRegistry();

		if ($this->typeCasters === []) {
			return $registry;
		}

		return new TypeCasterRegistry($this->typeCasters, $registry);
	}

	private function baseTypeCasterRegistry(): Contract\TypeCasterRegistry
	{
		if ($this->typeCasterRegistry === null) {
			$this->typeCasterRegistry = TypeCasterRegistry::withDefaults($this->messages());
		}

		return $this->typeCasterRegistry;
	}

	private function loadedValidatorParser(): Contract\ValidatorParser
	{
		return $this->validatorParser ??= new ValidatorParser();
	}
}
