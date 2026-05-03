<?php

declare(strict_types=1);

namespace Duon\Sire;

/** @api */
final readonly class Coercion implements Contract\Coercion
{
	public function __construct(
		public mixed $value,
		public mixed $pristine,
		public ?string $error = null,
	) {}
}
