<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Validator;
use Duon\Sire\Value;

class ValidatorTest extends TestCase
{
	public function testValidatorValidates(): void
	{
		$validator = new Validator(
			'same',
			'Same',
			static function (Value $value, string $compare): bool {
				return $value->value === $compare;
			},
			false,
		);

		$value = new Value('testvalue', 'testvalue');
		$this->assertTrue($validator->validate($value, 'testvalue'));
		$value = new Value('wrongvalue', 'wrongvalue');
		$this->assertFalse($validator->validate($value, 'testvalue'));
		$value = new Value(null, null);
		$this->assertFalse($validator->validate($value, 'testvalue'));
	}
}
