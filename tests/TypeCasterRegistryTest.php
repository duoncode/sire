<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Closure;
use Duon\Sire\Contract\TypeCaster as TypeCasterContract;
use Duon\Sire\Contract\TypeCasterRegistry as TypeCasterRegistryContract;
use Duon\Sire\TypeCasterRegistry;
use Duon\Sire\Value;
use Override;
use RuntimeException;

class TypeCasterRegistryTest extends TestCase
{
	public function testWithManyAddsCasters(): void
	{
		$registry = new TypeCasterRegistry();

		$updatedRegistry = $registry->withMany([
			'upper' => self::caster(static fn(mixed $pristine): string => strtoupper((string) $pristine)),
			'lower' => self::caster(static fn(mixed $pristine): string => strtolower((string) $pristine)),
		]);

		$this->assertNull($registry->get('upper'));
		$this->assertSame($updatedRegistry->get('upper'), $updatedRegistry->get('upper'));
		$this->assertInstanceOf(TypeCasterContract::class, $updatedRegistry->get('lower'));
	}

	public function testWithDefaultsHasBuiltInCasters(): void
	{
		$registry = TypeCasterRegistry::withDefaults(self::messages());

		$this->assertInstanceOf(TypeCasterContract::class, $registry->get('text'));
		$this->assertInstanceOf(TypeCasterContract::class, $registry->get('bool'));
		$this->assertInstanceOf(TypeCasterContract::class, $registry->get('int'));
		$this->assertInstanceOf(TypeCasterContract::class, $registry->get('float'));
		$this->assertInstanceOf(TypeCasterContract::class, $registry->get('list'));
	}

	public function testWithDefaultsMemoizesBuiltInCasters(): void
	{
		$registry = TypeCasterRegistry::withDefaults(self::messages());

		$this->assertSame($registry->get('text'), $registry->get('text'));
	}

	public function testWithDefaultsReturnsNullForUnknownCasters(): void
	{
		$registry = TypeCasterRegistry::withDefaults(self::messages());

		$this->assertNull($registry->get('unknown'));
	}

	public function testCustomCasterShadowsDefaults(): void
	{
		$caster = self::caster(static fn(mixed $pristine): string => (string) $pristine);
		$registry = TypeCasterRegistry::withDefaults(self::messages())->with('text', $caster);

		$this->assertSame($caster, $registry->get('text'));
	}

	public function testLocalCasterShadowsFallback(): void
	{
		$fallback = new class implements TypeCasterRegistryContract {
			#[Override]
			public function get(string $name): ?TypeCasterContract
			{
				throw new RuntimeException('Fallback should not be queried');
			}
		};

		$caster = self::caster(static fn(mixed $pristine): string => (string) $pristine);
		$registry = new TypeCasterRegistry(['text' => $caster], $fallback);

		$this->assertSame($caster, $registry->get('text'));
	}

	/** @return array<string, string> */
	private static function messages(): array
	{
		return [
			'bool' => 'Invalid boolean',
			'float' => 'Invalid number',
			'int' => 'Invalid number',
			'list' => 'Invalid list',
		];
	}

	/** @param Closure(mixed): mixed $callback */
	private static function caster(Closure $callback): TypeCasterContract
	{
		return new class($callback) implements TypeCasterContract {
			/** @param Closure(mixed): mixed $callback */
			public function __construct(
				private readonly Closure $callback,
			) {}

			#[Override]
			public function cast(mixed $pristine, string $label): Value
			{
				return new Value(($this->callback)($pristine), $pristine);
			}
		};
	}
}
