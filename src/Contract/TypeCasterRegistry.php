<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface TypeCasterRegistry
{
	public function get(string $name): ?TypeCaster;
}
