<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Contract;
use Duon\Sire\RuleRegistry;
use Duon\Sire\Validation;
use Duon\Sire\Value;
use Override;
use RuntimeException;

class RuleRegistryTest extends TestCase
{
	public function testWithManyAddsRules(): void
	{
		$registry = new RuleRegistry();

		$updatedRegistry = $registry->withMany([
			'starts_with' => self::stringRule(),
			'ends_with' => self::stringRule(),
		]);

		$this->assertNull($registry->get('starts_with'));
		$this->assertSame($updatedRegistry->get('starts_with'), $updatedRegistry->get('starts_with'));
		self::assertValid(self::rule($updatedRegistry, 'ends_with'), new Value('value', 'value'));
	}

	public function testWithManyHandlesEmptyInput(): void
	{
		$registry = RuleRegistry::withDefaults();
		$updatedRegistry = $registry->withMany([]);

		$this->assertSame($registry->get('required'), $updatedRegistry->get('required'));
	}

	public function testWithDefaultsFindsBuiltInRules(): void
	{
		$registry = RuleRegistry::withDefaults();

		self::assertValid(self::rule($registry, 'required'), new Value('value', 'value'));
		self::assertValid(
			self::rule($registry, 'email'),
			new Value('test@example.com', 'test@example.com'),
		);
		self::assertValid(self::rule($registry, 'minlen'), new Value('abc', 'abc'), '2');
		self::assertValid(self::rule($registry, 'maxlen'), new Value('abc', 'abc'), '3');
		self::assertValid(self::rule($registry, 'min'), new Value(5, 5), '1');
		self::assertValid(self::rule($registry, 'max'), new Value(5, 5), '10');
		self::assertValid(self::rule($registry, 'regex'), new Value('abc', 'abc'), '/^abc$/');
		self::assertValid(self::rule($registry, 'in'), new Value('a', 'a'), 'a,b');
	}

	public function testWithDefaultsMemoizesBuiltInRules(): void
	{
		$registry = RuleRegistry::withDefaults();

		$this->assertSame($registry->get('required'), $registry->get('required'));
	}

	public function testWithDefaultsReturnsNullForUnknownRules(): void
	{
		$registry = RuleRegistry::withDefaults();

		$this->assertNull($registry->get('unknown'));
	}

	public function testCustomRuleShadowsDefaults(): void
	{
		$rule = self::stringRule();
		$registry = RuleRegistry::withDefaults()->with('required', $rule);

		$this->assertSame($rule, $registry->get('required'));
	}

	public function testLocalRuleShadowsFallback(): void
	{
		$fallback = new class implements Contract\RuleRegistry {
			#[Override]
			public function get(string $name): ?Contract\Rule
			{
				throw new RuntimeException('Fallback should not be queried');
			}
		};

		$rule = self::stringRule();
		$registry = new RuleRegistry(['required' => $rule], $fallback);

		$this->assertSame($rule, $registry->get('required'));
	}

	private static function rule(RuleRegistry $registry, string $name): Contract\Rule
	{
		return $registry->get($name) ?? throw new RuntimeException(sprintf('Missing rule "%s"', $name));
	}

	private static function assertValid(
		Contract\Rule $rule,
		Value $value,
		string ...$args,
	): void {
		self::assertNull($rule->validate($value, ...$args)->failure);
	}

	private static function stringRule(): Contract\Rule
	{
		return new class implements Contract\Rule {
			public string $message {
				get => 'Must match';
			}

			#[Override]
			public function validate(Contract\Value $value, string ...$args): Contract\Validation
			{
				return Validation::from(is_string($value->value));
			}
		};
	}
}
