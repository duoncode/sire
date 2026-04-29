<?php

declare(strict_types=1);

namespace Duon\Sire\Contract;

/** @api */
interface ValidatorDefinitionParser
{
	/** @return array{name: string, args: list<string>} */
	public function parse(string $validatorDefinition): array;
}
