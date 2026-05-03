<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Contract;
use Duon\Sire\Validator\Required;

class ValidatorTest extends TestCase
{
	public function testBuiltInValidatorExposesMetadata(): void
	{
		$validator = new Required();

		$this->assertSame('Required', $validator->message);
		$this->assertFalse($validator->skipEmpty);
	}

	public function testBuiltInValidatorAcceptsValueImplementation(): void
	{
		$validator = new Required();

		$value = new class implements Contract\Value {
			public mixed $value = 'testvalue';
			public mixed $pristine = 'rawvalue';
			public array|string|null $error = null;
		};

		$this->assertTrue($validator->validate($value));
	}
}
