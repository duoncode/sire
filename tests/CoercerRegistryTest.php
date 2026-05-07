<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Closure;
use Duon\Sire\CoercerRegistry;
use Duon\Sire\Coercion;
use Duon\Sire\CoercionMode;
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
		$this->assertSame(
			'value',
			$updatedRegistry->get('lower')?->coerce('VALUE', CoercionMode::Coerce)->value,
		);
	}

	public function testWithDefaultsHasBuiltInCoercers(): void
	{
		$registry = CoercerRegistry::withDefaults();

		$this->assertSame('test', $registry->get('string')?->coerce('test', CoercionMode::Coerce)->value);
		$this->assertSame(true, $registry->get('bool')?->coerce(true, CoercionMode::Coerce)->value);
		$this->assertSame(13, $registry->get('int')?->coerce('13', CoercionMode::Coerce)->value);
		$this->assertSame(13.0, $registry->get('float')?->coerce('13', CoercionMode::Coerce)->value);
		$this->assertSame(13, $registry->get('number')?->coerce('13', CoercionMode::Coerce)->value);
		$this->assertSame([1, 2], $registry->get('list')?->coerce([1, 2], CoercionMode::Coerce)->value);
	}

	public function testWithDefaultsMemoizesBuiltInCoercers(): void
	{
		$registry = CoercerRegistry::withDefaults();

		$this->assertSame($registry->get('string'), $registry->get('string'));
	}

	public function testWithDefaultsReturnsNullForUnknownCoercers(): void
	{
		$registry = CoercerRegistry::withDefaults();

		$this->assertNull($registry->get('unknown'));
	}

	public function testCustomCoercerShadowsDefaults(): void
	{
		$coercer = self::coercer(static fn(mixed $pristine): string => (string) $pristine);
		$registry = CoercerRegistry::withDefaults()->with('string', $coercer);

		$this->assertSame($coercer, $registry->get('string'));
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
		$registry = new CoercerRegistry(['string' => $coercer], $fallback);

		$this->assertSame($coercer, $registry->get('string'));
	}

	/** @param Closure(mixed): mixed $callback */
	private static function coercer(Closure $callback): Coercer
	{
		return new class($callback) implements Coercer {
			public string $message {
				get => 'Invalid value';
			}

			/** @param Closure(mixed): mixed $callback */
			public function __construct(
				private readonly Closure $callback,
			) {}

			#[Override]
			public function coerce(
				mixed $pristine,
				CoercionMode $mode,
			): Contract\Coercion {
				return new Coercion(($this->callback)($pristine), $pristine);
			}
		};
	}
}
