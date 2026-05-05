<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface RuleRegistry
{
	public function get(string $name): ?Rule;
}
