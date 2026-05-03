<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Coercion;

class CoercionTest extends TestCase
{
	public function testProperties(): void
	{
		$coercion = new Coercion('coerced', 'raw', 'Invalid');

		$this->assertSame('coerced', $coercion->value);
		$this->assertSame('raw', $coercion->pristine);
		$this->assertSame('Invalid', $coercion->error);
	}

	public function testErrorDefaultsToNull(): void
	{
		$coercion = new Coercion('coerced', 'raw');

		$this->assertNull($coercion->error);
	}
}
