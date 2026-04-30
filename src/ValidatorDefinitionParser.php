<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;
use ValueError;

/** @api */
final class ValidatorDefinitionParser implements Contract\ValidatorDefinitionParser
{
	#[Override]
	/** @return array{name: string, args: list<string>} */
	public function parse(string $validatorDefinition): array
	{
		$validatorArray = DslSplitter::split($validatorDefinition, ':', true);
		$validatorName = $validatorArray[0] ?? '';

		if ($validatorName === '') {
			throw new ValueError('Invalid validator definition: missing validator name');
		}

		$validatorArgs = array_map(
			$this->unquoteWrappedArgument(...),
			array_slice($validatorArray, 1),
		);

		return [
			'name' => $validatorName,
			'args' => $validatorArgs,
		];
	}

	private function unquoteWrappedArgument(string $arg): string
	{
		if (strlen($arg) < 2) {
			return $arg;
		}

		$firstChar = $arg[0];
		$lastChar = $arg[strlen($arg) - 1];

		if (
			($firstChar === '"' || $firstChar === "'")
			&& $firstChar === $lastChar
			&& substr_count($arg, $firstChar) === 2
		) {
			return substr($arg, 1, -1);
		}

		return $arg;
	}
}
