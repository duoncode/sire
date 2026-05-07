<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;

/** @internal */
final readonly class ShapeDefinition
{
	/**
	 * @param array<string, Field> $fields
	 * @param list<Closure(array<array-key, mixed>): array<array-key, mixed>> $prepareCallbacks
	 * @param list<Closure(Review): void> $reviewCallbacks
	 */
	public function __construct(
		public bool $list,
		public Extra $extra,
		public CoercionMode $coercionMode,
		public array $fields,
		public Contract\RuleRegistry $rules,
		public Contract\CoercerRegistry $coercers,
		public Contract\RuleParser $ruleParser,
		public MessageFormatter $messageFormatter,
		public array $prepareCallbacks,
		public array $reviewCallbacks,
	) {}
}
