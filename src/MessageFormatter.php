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

	/** Message templates receive label, field, pristine value, then failure arguments. */
	public function format(
		Failure $failure,
		string $label,
		string $field,
		mixed $pristine,
		?string $defaultKey = null,
		string $fallback = 'Invalid value',
	): string {
		$template = null;

		if ($failure->key !== '') {
			$template = $this->messages[$failure->key] ?? null;
		}

		if ($template === null && $defaultKey !== null) {
			$template = $this->messages[$defaultKey] ?? null;
		}

		$template ??= $failure->fallback ?? $fallback;

		return $this->formatTemplate($template, $label, $field, $pristine, $failure->args);
	}

	/** @param list<mixed> $args */
	public function formatTemplate(
		string $template,
		string $label,
		string $field,
		mixed $pristine,
		array $args = [],
	): string {
		if (self::usesNamedTemplate($template)) {
			return self::formatNamed($template, $label, $field, $pristine, $args);
		}

		return sprintf(
			$template,
			$label,
			$field,
			self::stringify($pristine),
			...$args,
		);
	}

	/** @param list<mixed> $args */
	private static function formatNamed(
		string $template,
		string $label,
		string $field,
		mixed $pristine,
		array $args,
	): string {
		$formatted = preg_replace_callback(
			'/{{|}}|{([^{}]+)}/',
			static function (array $matches) use ($label, $field, $pristine, $args): string {
				$token = $matches[0];

				return match ($token) {
					'{{' => '{',
					'}}' => '}',
					default => self::placeholder(
						$matches[1] ?? '',
						$token,
						$label,
						$field,
						$pristine,
						$args,
					),
				};
			},
			$template,
		);

		return $formatted ?? $template;
	}

	/** @param list<mixed> $args */
	private static function placeholder(
		string $name,
		string $text,
		string $label,
		string $field,
		mixed $pristine,
		array $args,
	): string {
		if ($name === 'label') {
			return $label;
		}

		if ($name === 'field') {
			return $field;
		}

		if ($name === 'value') {
			return self::stringify($pristine);
		}

		$index = self::argumentIndex($name);

		if ($index !== null && array_key_exists($index, $args)) {
			return self::stringify($args[$index]);
		}

		return $text;
	}

	private static function argumentIndex(string $name): ?int
	{
		if (preg_match('/^arg([1-9][0-9]*)$/', $name, $matches) !== 1) {
			return null;
		}

		return (int) $matches[1] - 1;
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
