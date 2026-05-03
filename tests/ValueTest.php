<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Value;

class ValueTest extends TestCase
{
	public function testPropertiesNumbers(): void
	{
		$value = new Value(1, 2);

		$this->assertSame(1, $value->value);
		$this->assertSame(2, $value->pristine);
	}

	public function testPropertiesStrings(): void
	{
		$value = new Value('test1', 'test2');

		$this->assertSame('test1', $value->value);
		$this->assertSame('test2', $value->pristine);
	}

	public function testPropertiesArrays(): void
	{
		$value = new Value([1, 2, 3], [2, 3, 4]);

		$this->assertSame([1, 2, 3], $value->value);
		$this->assertSame([2, 3, 4], $value->pristine);
	}
}
