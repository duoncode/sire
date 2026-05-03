<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface Validator
{
	public string $message { get; }

	public bool $skipEmpty { get; }

	public function validate(Value $value, string ...$args): bool;
}
