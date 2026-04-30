<?php

declare(strict_types=1);

namespace Duon\Sire;

use ValueError;

final class DslSplitter
{
	/** @return list<string> */
	public static function split(
		string $input,
		string $delimiter,
		bool $preserveQuotes = false,
	): array {
		$parts = [''];
		$index = 0;
		$quote = null;
		$length = strlen($input);

		for ($i = 0; $i < $length; $i++) {
			$char = $input[$i];

			if ($char === '\\') {
				$nextChar = ($i + 1) < $length ? $input[$i + 1] : null;

				if ($nextChar !== null && self::isEscapable($nextChar, $delimiter)) {
					$parts[$index] .= $nextChar;
					$i++;

					continue;
				}

				$parts[$index] .= $char;

				continue;
			}

			if ($quote !== null) {
				if ($char === $quote) {
					if ($preserveQuotes) {
						$parts[$index] .= $char;
					}

					$quote = null;

					continue;
				}

				$parts[$index] .= $char;

				continue;
			}

			if ($char === '"' || $char === "'") {
				if ($preserveQuotes) {
					$parts[$index] .= $char;
				}

				$quote = $char;

				continue;
			}

			if ($char === $delimiter) {
				$parts[] = '';
				$index = count($parts) - 1;

				continue;
			}

			$parts[$index] .= $char;
		}

		if ($quote !== null) {
			throw new ValueError('Invalid validator definition: unclosed quote');
		}

		return $parts;
	}

	private static function isEscapable(string $char, string $delimiter): bool
	{
		return in_array($char, [$delimiter, '\\', '"', "'"], true);
	}
}
