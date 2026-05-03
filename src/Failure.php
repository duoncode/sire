<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @api */
final readonly class Failure
{
	/** @param list<mixed> $args */
	public function __construct(
		public string $key,
		public array $args = [],
		public ?string $fallback = null,
	) {}

	public static function key(string $key, mixed ...$args): self
	{
		/** @var list<mixed> $list */
		$list = $args;

		return new self($key, $list);
	}

	public static function message(string $message): self
	{
		return new self('', fallback: $message);
	}
}
