<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Coercion;
use Duon\Sire\Failure;

class CoercionTest extends TestCase
{
	public function testProperties(): void
	{
		$failure = new Failure('type.custom');
		$coercion = new Coercion('coerced', 'raw', $failure);

		$this->assertSame('coerced', $coercion->value);
		$this->assertSame('raw', $coercion->pristine);
		$this->assertSame($failure, $coercion->failure);
	}

	public function testFailureDefaultsToNull(): void
	{
		$coercion = new Coercion('coerced', 'raw');

		$this->assertNull($coercion->failure);
	}
}
