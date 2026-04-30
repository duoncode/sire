<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\TypeCaster;
use Duon\Sire\TypeCasterRegistry;
use Duon\Sire\Value;

class TypeCasterRegistryTest extends TestCase
{
	public function testWithManyAddsCasters(): void
	{
		$registry = new TypeCasterRegistry();

		$updatedRegistry = $registry->withMany([
			'upper' => new TypeCaster(
				static fn(mixed $pristine, string $label): Value => new Value(
					strtoupper((string) $pristine),
					$pristine,
				),
			),
			'lower' => new TypeCaster(
				static fn(mixed $pristine, string $label): Value => new Value(
					strtolower((string) $pristine),
					$pristine,
				),
			),
		]);

		$this->assertCount(0, $registry->all());
		$this->assertCount(2, $updatedRegistry->all());
		$this->assertArrayHasKey('upper', $updatedRegistry->all());
		$this->assertArrayHasKey('lower', $updatedRegistry->all());
	}

	public function testWithDefaultsHasBuiltInCasters(): void
	{
		$registry = TypeCasterRegistry::withDefaults([
			'bool' => 'Invalid boolean',
			'float' => 'Invalid number',
			'int' => 'Invalid number',
			'list' => 'Invalid list',
		]);

		$this->assertArrayHasKey('text', $registry->all());
		$this->assertArrayHasKey('bool', $registry->all());
		$this->assertArrayHasKey('int', $registry->all());
		$this->assertArrayHasKey('float', $registry->all());
		$this->assertArrayHasKey('list', $registry->all());
	}
}
