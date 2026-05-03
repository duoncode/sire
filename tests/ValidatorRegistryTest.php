<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Contract\ValidatorRegistry as ValidatorRegistryContract;
use Duon\Sire\Validator;
use Duon\Sire\ValidatorRegistry;
use Duon\Sire\Value;
use Override;
use RuntimeException;

class ValidatorRegistryTest extends TestCase
{
	public function testWithManyAddsValidators(): void
	{
		$registry = new ValidatorRegistry();

		$updatedRegistry = $registry->withMany([
			'starts_with' => self::stringValidator('starts_with'),
			'ends_with' => self::stringValidator('ends_with'),
		]);

		$this->assertNull($registry->get('starts_with'));
		$this->assertSame($updatedRegistry->get('starts_with'), $updatedRegistry->get('starts_with'));
		$this->assertInstanceOf(Validator::class, $updatedRegistry->get('ends_with'));
	}

	public function testWithManyHandlesEmptyInput(): void
	{
		$registry = ValidatorRegistry::withDefaults();
		$updatedRegistry = $registry->withMany([]);

		$this->assertSame($registry->get('required'), $updatedRegistry->get('required'));
	}

	public function testLocalValidatorShadowsFallback(): void
	{
		$fallback = new class implements ValidatorRegistryContract {
			#[Override]
			public function get(string $name): ?Validator
			{
				throw new RuntimeException('Fallback should not be queried');
			}
		};

		$validator = self::stringValidator('required');
		$registry = new ValidatorRegistry(['required' => $validator], $fallback);

		$this->assertSame($validator, $registry->get('required'));
	}

	private static function stringValidator(string $name): Validator
	{
		return new Validator(
			$name,
			'Must match',
			static fn(Value $value, string ...$_args): bool => is_string($value->value),
			true,
		);
	}
}
