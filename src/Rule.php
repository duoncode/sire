<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;

/** @api */
final class Rule
{
	private ?string $label = null;

	/** @var list<Closure(mixed): mixed> */
	private array $preparers = [];

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

	/** @param Closure(mixed): mixed $callback */
	public function prepare(Closure $callback): static
	{
		$this->preparers[] = $callback;

		return $this;
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

	public function applyPreparation(mixed $value): mixed
	{
		foreach ($this->preparers as $prepare) {
			$value = $prepare($value);
		}

		return $value;
	}

	private function messageKey(string $key): string
	{
		if ($key === 'type') {
			return 'type.' . $this->type();
		}

		if (str_starts_with($key, 'type.') || str_starts_with($key, 'validator.')) {
			return $key;
		}

		return 'validator.' . $key;
	}
}
