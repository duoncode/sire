<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Validator;
use Duon\Sire\ValidatorRegistry;
use Duon\Sire\Value;

class ValidatorRegistryTest extends TestCase
{
	public function testWithManyAddsValidators(): void
	{
		$registry = new ValidatorRegistry();

		$updatedRegistry = $registry->withMany([
			'starts_with' => new Validator(
				'starts_with',
				'Must start with %4$s',
				static function (Value $value, string ...$args): bool {
					$prefix = $args[0] ?? '';

					return str_starts_with((string) $value->value, $prefix);
				},
				true,
			),
			'ends_with' => new Validator(
				'ends_with',
				'Must end with %4$s',
				static function (Value $value, string ...$args): bool {
					$suffix = $args[0] ?? '';

					return str_ends_with((string) $value->value, $suffix);
				},
				true,
			),
		]);

		$this->assertCount(0, $registry->all());
		$this->assertCount(2, $updatedRegistry->all());
		$this->assertArrayHasKey('starts_with', $updatedRegistry->all());
		$this->assertArrayHasKey('ends_with', $updatedRegistry->all());
	}

	public function testWithManyHandlesEmptyInput(): void
	{
		$registry = ValidatorRegistry::withDefaults();
		$updatedRegistry = $registry->withMany([]);

		$this->assertSame(array_keys($registry->all()), array_keys($updatedRegistry->all()));
	}
}
