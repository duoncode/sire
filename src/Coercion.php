<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @api */
final readonly class Coercion implements Contract\Coercion
{
	public function __construct(
		public Contract\Value $value,
		public ?string $error = null,
	) {}
}
