<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Coercion;
use Duon\Sire\Value;

class CoercionTest extends TestCase
{
	public function testProperties(): void
	{
		$value = new Value('coerced', 'raw');
		$coercion = new Coercion($value, 'Invalid');

		$this->assertSame($value, $coercion->value);
		$this->assertSame('Invalid', $coercion->error);
	}

	public function testErrorDefaultsToNull(): void
	{
		$coercion = new Coercion(new Value('coerced', 'raw'));

		$this->assertNull($coercion->error);
	}
}
