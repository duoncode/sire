<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface RuleParser
{
	/** @return array{name: string, args: list<string>} */
	public function parse(string $ruleDefinition): array;
}
