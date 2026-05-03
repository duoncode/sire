<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Contract;
use Duon\Sire\Validator;
use Duon\Sire\Value;

class ValidatorTest extends TestCase
{
	public function testValidatorValidates(): void
	{
		$validator = new Validator(
			'same',
			'Same',
			static fn(Value $value, string $compare): bool => $value->value === $compare,
			false,
		);

		$value = new Value('testvalue', 'testvalue');
		$this->assertTrue($validator->validate($value, 'testvalue'));
		$value = new Value('wrongvalue', 'wrongvalue');
		$this->assertFalse($validator->validate($value, 'testvalue'));
		$value = new Value(null, null);
		$this->assertFalse($validator->validate($value, 'testvalue'));
	}

	public function testValidatorAcceptsValueImplementation(): void
	{
		$validator = new Validator(
			'same',
			'Same',
			static fn(Contract\Value $value, string $compare): bool => $value->value === $compare,
			false,
		);

		$value = new class implements Contract\Value {
			public mixed $value = 'testvalue';
			public mixed $pristine = 'rawvalue';
			public array|string|null $error = null;
		};

		$this->assertTrue($validator->validate($value, 'testvalue'));
	}
}
