<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Contract;
use Duon\Sire\Contract\Value;
use Duon\Sire\ValidatorRegistry;
use Override;
use RuntimeException;

class ValidatorRegistryTest extends TestCase
{
	public function testWithManyAddsValidators(): void
	{
		$registry = new ValidatorRegistry();

		$updatedRegistry = $registry->withMany([
			'starts_with' => self::stringValidator(),
			'ends_with' => self::stringValidator(),
		]);

		$this->assertNull($registry->get('starts_with'));
		$this->assertSame($updatedRegistry->get('starts_with'), $updatedRegistry->get('starts_with'));
		$this->assertInstanceOf(Contract\Validator::class, $updatedRegistry->get('ends_with'));
	}

	public function testWithManyHandlesEmptyInput(): void
	{
		$registry = ValidatorRegistry::withDefaults();
		$updatedRegistry = $registry->withMany([]);

		$this->assertSame($registry->get('required'), $updatedRegistry->get('required'));
	}

	public function testWithDefaultsFindsBuiltInValidators(): void
	{
		$registry = ValidatorRegistry::withDefaults();

		$this->assertInstanceOf(Contract\Validator::class, $registry->get('required'));
		$this->assertInstanceOf(Contract\Validator::class, $registry->get('email'));
		$this->assertInstanceOf(Contract\Validator::class, $registry->get('minlen'));
		$this->assertInstanceOf(Contract\Validator::class, $registry->get('maxlen'));
		$this->assertInstanceOf(Contract\Validator::class, $registry->get('min'));
		$this->assertInstanceOf(Contract\Validator::class, $registry->get('max'));
		$this->assertInstanceOf(Contract\Validator::class, $registry->get('regex'));
		$this->assertInstanceOf(Contract\Validator::class, $registry->get('in'));
	}

	public function testWithDefaultsMemoizesBuiltInValidators(): void
	{
		$registry = ValidatorRegistry::withDefaults();

		$this->assertSame($registry->get('required'), $registry->get('required'));
	}

	public function testWithDefaultsReturnsNullForUnknownValidators(): void
	{
		$registry = ValidatorRegistry::withDefaults();

		$this->assertNull($registry->get('unknown'));
	}

	public function testCustomValidatorShadowsDefaults(): void
	{
		$validator = self::stringValidator();
		$registry = ValidatorRegistry::withDefaults()->with('required', $validator);

		$this->assertSame($validator, $registry->get('required'));
	}

	public function testLocalValidatorShadowsFallback(): void
	{
		$fallback = new class implements Contract\ValidatorRegistry {
			#[Override]
			public function get(string $name): ?Contract\Validator
			{
				throw new RuntimeException('Fallback should not be queried');
			}
		};

		$validator = self::stringValidator();
		$registry = new ValidatorRegistry(['required' => $validator], $fallback);

		$this->assertSame($validator, $registry->get('required'));
	}

	private static function stringValidator(): Contract\Validator
	{
		return new class implements Contract\Validator {
			public string $message = 'Must match';

			#[Override]
			public function validate(Value $value, string ...$args): bool
			{
				return is_string($value->value);
			}
		};
	}
}
