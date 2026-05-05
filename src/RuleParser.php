<?php

declare(strict_types=1);

namespace Duon\Sire;

use Override;
use ValueError;

/** @api */
final class RuleParser implements Contract\RuleParser
{
	#[Override]
	/** @return array{name: string, args: list<string>} */
	public function parse(string $ruleDefinition): array
	{
		$ruleArray = DslSplitter::split($ruleDefinition, ':', true);
		$ruleName = $ruleArray[0] ?? '';

		if ($ruleName === '') {
			throw new ValueError('Invalid rule definition: missing rule name');
		}

		$ruleArgs = array_map(
			$this->unquoteWrappedArgument(...),
			array_slice($ruleArray, 1),
		);

		return [
			'name' => $ruleName,
			'args' => $ruleArgs,
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
