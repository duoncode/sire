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

	public function format(Failure $failure, string $label, string $field, mixed $pristine): string
	{
		$template = null;

		if ($failure->key !== '') {
			$template = $this->messages[$failure->key] ?? null;
		}

		$template ??= $failure->fallback ?? 'Invalid value';

		return sprintf(
			$template,
			$label,
			$field,
			print_r($pristine, true),
			...$failure->args,
		);
	}
}
