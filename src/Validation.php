<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @api */
final readonly class Validation implements Contract\Validation
{
	public function __construct(
		public ?Failure $failure = null,
	) {}

	public static function valid(): self
	{
		return new self();
	}

	public static function invalid(?Failure $failure = null): self
	{
		return new self($failure ?? Failure::invalid());
	}

	public static function from(bool $valid, ?Failure $failure = null): self
	{
		return $valid ? self::valid() : self::invalid($failure);
	}
}
