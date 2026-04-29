<?php

declare(strict_types=1);

namespace Duon\Sire;

use Closure;
use Override;

/** @api */
final class TypeCaster implements Contract\TypeCaster
{
	/** @var Closure(mixed, string): Value */
	private Closure $caster;

	/** @param Closure(mixed, string): Value $caster */
	public function __construct(Closure $caster)
	{
		$this->caster = $caster;
	}

	#[Override]
	public function cast(mixed $pristine, string $label): Value
	{
		return ($this->caster)($pristine, $label);
	}
}
