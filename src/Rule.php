<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @api */
final class Rule
{
	private ?string $label = null;

	/** @var list<callable> */
	private array $preparers = [];

	private bool $hasDefault = false;

	private mixed $default = null;

	private bool $nullable = false;

	private bool $optional = false;

	/** @var array<string, string> */
	private array $messages = [];

	/** @param list<string> $validators */
	public function __construct(
		public readonly string $field,
		public readonly string|Contract\Shape $type,
		public readonly array $validators,
	) {}

	public function label(string $label): static
	{
		$this->label = $label;

		return $this;
	}

	/** @param callable $callback */
	public function prepare(callable $callback): static
	{
		$this->preparers[] = $callback;

		return $this;
	}

	public function default(mixed $value): static
	{
		$this->default = $value;
		$this->hasDefault = true;

		if ($value === null) {
			$this->nullable();
		}

		return $this;
	}

	public function nullable(): static
	{
		$this->nullable = true;

		return $this;
	}

	public function optional(): static
	{
		$this->optional = true;

		return $this;
	}

	public function hasDefault(): bool
	{
		return $this->hasDefault;
	}

	public function defaultValue(): mixed
	{
		return $this->default;
	}

	public function isNullable(): bool
	{
		return $this->nullable;
	}

	public function isOptional(): bool
	{
		return $this->optional;
	}

	public function message(string $key, string $message): static
	{
		$this->messages[$this->messageKey($key)] = $message;

		return $this;
	}

	/** @param array<string, string> $messages */
	public function messages(array $messages): static
	{
		foreach ($messages as $key => $message) {
			$this->message($key, $message);
		}

		return $this;
	}

	/** @return array<string, string> */
	public function messageOverrides(): array
	{
		return $this->messages;
	}

	public function name(): string
	{
		return $this->label ?? $this->field;
	}

	public function type(): string
	{
		return is_string($this->type) ? $this->type : 'shape';
	}

	/** @param array<string, mixed> $data */
	public function applyPreparation(mixed $value, array $data): mixed
	{
		foreach ($this->preparers as $prepare) {
			$value = $prepare($value, $data);
		}

		return $value;
	}

	private function messageKey(string $key): string
	{
		if ($key === 'type') {
			return 'type.' . $this->type();
		}

		if ($key === 'missing' || $key === 'null') {
			return $key;
		}

		if (str_starts_with($key, 'type.') || str_starts_with($key, 'validator.')) {
			return $key;
		}

		return 'validator.' . $key;
	}
}
