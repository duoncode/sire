<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;

/** @internal */
final readonly class ShapeDefinition
{
	/**
	 * @param array<string, Field> $fields
	 * @param list<Closure(Review): void> $reviewCallbacks
	 */
	public function __construct(
		public bool $list,
		public Extra $extra,
		public array $fields,
		public Contract\ValidatorRegistry $validators,
		public Contract\CoercerRegistry $coercers,
		public Contract\ValidatorParser $validatorParser,
		public MessageFormatter $messageFormatter,
		public array $reviewCallbacks,
	) {}
}
