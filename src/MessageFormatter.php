<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @api */
final readonly class MessageFormatter
{
	/** @param array<string, string> $messages */
	public function __construct(
		private array $messages,
	) {}

	/**
	 * Message templates receive label, field, pristine value, then failure or validator arguments.
	 *
	 * @param list<mixed> $args
	 * @param array<string, string> $messages
	 */
	public function format(
		Failure $failure,
		string $label,
		string $field,
		mixed $pristine,
		?string $defaultKey = null,
		string $fallback = 'Invalid value',
		array $args = [],
		array $messages = [],
	): string {
		$template = $this->template($failure, $defaultKey, $fallback, $messages);
		$args = $failure->args === [] ? $args : $failure->args;

		return self::render($template, $label, $field, $pristine, $args);
	}

	/** @param array<string, string> $messages */
	private function template(
		Failure $failure,
		?string $defaultKey,
		string $fallback,
		array $messages,
	): string {
		if ($failure->key !== '' && array_key_exists($failure->key, $messages)) {
			return $messages[$failure->key];
		}

		if ($defaultKey !== null && array_key_exists($defaultKey, $messages)) {
			return $messages[$defaultKey];
		}

		if ($failure->key !== '' && array_key_exists($failure->key, $this->messages)) {
			return $this->messages[$failure->key];
		}

		if ($defaultKey !== null && array_key_exists($defaultKey, $this->messages)) {
			return $this->messages[$defaultKey];
		}

		return $failure->fallback ?? $fallback;
	}

	/** @param list<mixed> $args */
	private static function render(
		string $template,
		string $label,
		string $field,
		mixed $pristine,
		array $args,
	): string {
		if (self::usesNamedTemplate($template)) {
			return self::renderNamed(
				$template,
				self::placeholders($label, $field, $pristine, $args),
			);
		}

		return sprintf(
			$template,
			$label,
			$field,
			self::stringify($pristine),
			...$args,
		);
	}

	/** @param array<string, string> $values */
	private static function renderNamed(string $template, array $values): string
	{
		$rendered = preg_replace_callback(
			'/{{|}}|{([^{}]+)}/',
			static function (array $matches) use ($values): string {
				$token = $matches[0];

				return match ($token) {
					'{{' => '{',
					'}}' => '}',
					default => $values[$matches[1] ?? ''] ?? $token,
				};
			},
			$template,
		);

		return $rendered ?? $template;
	}

	/**
	 * @param list<mixed> $args
	 * @return array<string, string>
	 */
	private static function placeholders(
		string $label,
		string $field,
		mixed $pristine,
		array $args,
	): array {
		$values = [
			'label' => $label,
			'field' => $field,
			'value' => self::stringify($pristine),
		];

		foreach ($args as $index => $arg) {
			$values['arg' . ($index + 1)] = self::stringify($arg);
		}

		return $values;
	}

	private static function stringify(mixed $value): string
	{
		return print_r($value, true);
	}

	private static function usesNamedTemplate(string $template): bool
	{
		return (
			str_contains($template, '{{')
			|| str_contains($template, '}}')
			|| preg_match('/{(?:label|field|value|arg[1-9][0-9]*)}/', $template) === 1
		);
	}
}
