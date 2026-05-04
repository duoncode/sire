<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Failure;
use Duon\Sire\Validation;

class ValidationTest extends TestCase
{
	public function testValid(): void
	{
		$this->assertNull(Validation::valid()->failure);
		$this->assertNull(Validation::from(true)->failure);
	}

	public function testInvalid(): void
	{
		$this->assertSame('', Validation::invalid()->failure?->key);
		$this->assertSame(
			'type.custom',
			Validation::from(false, Failure::key('type.custom'))->failure?->key,
		);
	}
}
