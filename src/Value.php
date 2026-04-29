<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @api */
class Value
{
	public function __construct(
		public readonly mixed $value,
		public readonly mixed $pristine,
		public readonly array|string|null $error = null,
	) {}
}
