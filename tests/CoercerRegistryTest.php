<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Closure;
use Duon\Sire\CoercerRegistry;
use Duon\Sire\Coercion;
use Duon\Sire\Contract;
use Duon\Sire\Contract\Coercer;
use Override;
use RuntimeException;

class CoercerRegistryTest extends TestCase
{
	public function testWithManyAddsCoercers(): void
	{
		$registry = new CoercerRegistry();

		$updatedRegistry = $registry->withMany([
			'upper' => self::coercer(static fn(mixed $pristine): string => strtoupper((string) $pristine)),
			'lower' => self::coercer(static fn(mixed $pristine): string => strtolower((string) $pristine)),
		]);

		$this->assertNull($registry->get('upper'));
		$this->assertSame($updatedRegistry->get('upper'), $updatedRegistry->get('upper'));
		$this->assertInstanceOf(Coercer::class, $updatedRegistry->get('lower'));
	}

	public function testWithDefaultsHasBuiltInCoercers(): void
	{
		$registry = CoercerRegistry::withDefaults();

		$this->assertInstanceOf(Coercer::class, $registry->get('text'));
		$this->assertInstanceOf(Coercer::class, $registry->get('bool'));
		$this->assertInstanceOf(Coercer::class, $registry->get('int'));
		$this->assertInstanceOf(Coercer::class, $registry->get('float'));
		$this->assertInstanceOf(Coercer::class, $registry->get('list'));
	}

	public function testWithDefaultsMemoizesBuiltInCoercers(): void
	{
		$registry = CoercerRegistry::withDefaults();

		$this->assertSame($registry->get('text'), $registry->get('text'));
	}

	public function testWithDefaultsReturnsNullForUnknownCoercers(): void
	{
		$registry = CoercerRegistry::withDefaults();

		$this->assertNull($registry->get('unknown'));
	}

	public function testCustomCoercerShadowsDefaults(): void
	{
		$coercer = self::coercer(static fn(mixed $pristine): string => (string) $pristine);
		$registry = CoercerRegistry::withDefaults()->with('text', $coercer);

		$this->assertSame($coercer, $registry->get('text'));
	}

	public function testLocalCoercerShadowsFallback(): void
	{
		$fallback = new class implements Contract\CoercerRegistry {
			#[Override]
			public function get(string $name): ?Coercer
			{
				throw new RuntimeException('Fallback should not be queried');
			}
		};

		$coercer = self::coercer(static fn(mixed $pristine): string => (string) $pristine);
		$registry = new CoercerRegistry(['text' => $coercer], $fallback);

		$this->assertSame($coercer, $registry->get('text'));
	}

	/** @param Closure(mixed): mixed $callback */
	private static function coercer(Closure $callback): Coercer
	{
		return new class($callback) implements Coercer {
			/** @param Closure(mixed): mixed $callback */
			public function __construct(
				private readonly Closure $callback,
			) {}

			#[Override]
			public function coerce(mixed $pristine, string $label): Contract\Coercion
			{
				return new Coercion(($this->callback)($pristine), $pristine);
			}
		};
	}
}
