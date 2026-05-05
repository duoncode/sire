<?php

declare(strict_types=1);

namespace Duon\Sire;

use JsonSerializable;
use Override;

/** @api */
final readonly class Result implements JsonSerializable
{
	/**
	 * @param list<Issue> $issues
	 * @param array<array-key, mixed> $values
	 */
	public function __construct(
		private array $issues,
		private array $values,
	) {}

	public function valid(): bool
	{
		return $this->issues === [];
	}

	/** @return list<Issue> */
	public function issues(): array
	{
		return $this->issues;
	}

	/** @return array<array-key, mixed> */
	public function values(): array
	{
		return $this->values;
	}

	/**
	 * @param string|int|list<string|int> $path
	 * @return list<string>
	 */
	public function messages(string|int|array $path = []): array
	{
		$path = self::path($path);
		$messages = [];

		foreach ($this->issues as $issue) {
			if ($issue->path !== $path) {
				continue;
			}

			$messages[] = $issue->message;
		}

		return $messages;
	}

	/** @param string|int|list<string|int> $path */
	public function first(string|int|array $path = []): ?string
	{
		return $this->messages($path)[0] ?? null;
	}

	/** @param string|int|list<string|int> $path */
	public function has(string|int|array $path = []): bool
	{
		return $this->messages($path) !== [];
	}

	/** @return array{valid: bool, issues: list<Issue>} */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'valid' => $this->valid(),
			'issues' => $this->issues,
		];
	}

	/**
	 * @param string|int|list<string|int> $path
	 * @return list<string|int>
	 */
	private static function path(string|int|array $path): array
	{
		if (is_int($path)) {
			return [$path];
		}

		if (is_string($path)) {
			if ($path === '') {
				return [];
			}

			return self::normalizePath(explode('.', $path));
		}

		return $path;
	}

	/**
	 * @param list<string> $path
	 * @return list<string|int>
	 */
	private static function normalizePath(array $path): array
	{
		return array_map(
			static fn(string $part): string|int => ctype_digit($part) ? (int) $part : $part,
			$path,
		);
	}
}
