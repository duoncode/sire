<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Blank;
use Duon\Sire\Coercion;
use Duon\Sire\CoercionMode;
use Duon\Sire\Contract;
use Duon\Sire\Shape;
use Duon\Sire\Validation;
use Override;
use ValueError;

class EmptyTest extends TestCase
{
	public function testRejectsInvalidBlank(): void
	{
		$this->expectException(ValueError::class);

		new Shape()->add('name', 'string')->empty('blank');
	}

	public function testAcceptsStringBlank(): void
	{
		$shape = new Shape();
		$shape->add('name', 'string')->empty('missing', Blank::Null);

		$result = $shape->validate(['name' => 'Ada']);

		$this->assertTrue($result->valid());
		$this->assertSame('Ada', $result->values()['name']);
	}

	public function testFieldDefaultValueDoesNotFillNullByDefault(): void
	{
		$shape = new Shape();
		$shape->add('status', 'string')->label('Status')->default('draft');

		$result = $shape->validate(['status' => null]);

		$this->assertFalse($result->valid());
		$this->assertSame('Status must not be null', $result->first('status'));
		$this->assertNull($result->values()['status']);
	}

	public function testFieldEmptyNullDefaultFillsNullAndMissingValues(): void
	{
		$shape = new Shape();
		$shape
			->add('status', 'string')
			->empty(Blank::Missing, Blank::Null)
			->default('draft');

		$nullResult = $shape->validate(['status' => null]);
		$missingResult = $shape->validate([]);

		$this->assertTrue($nullResult->valid());
		$this->assertSame('draft', $nullResult->values()['status']);
		$this->assertTrue($missingResult->valid());
		$this->assertSame('draft', $missingResult->values()['status']);
	}

	public function testFieldEmptyNullDefaultDoesNotFillMissingWithoutMissingEmpty(): void
	{
		$shape = new Shape();
		$shape->add('status', 'string')->empty(Blank::Null)->default('draft');

		$result = $shape->validate([]);

		$this->assertFalse($result->valid());
		$this->assertSame('status is required', $result->first('status'));
		$this->assertSame([], $result->values());
	}

	public function testFieldEmptyStringDefaultMatchesExactString(): void
	{
		$shape = new Shape();
		$shape->add('status', 'string')->empty(Blank::String)->default('draft');

		$emptyResult = $shape->validate(['status' => '']);
		$spaceResult = $shape->validate(['status' => ' ']);

		$this->assertTrue($emptyResult->valid());
		$this->assertSame('draft', $emptyResult->values()['status']);
		$this->assertTrue($spaceResult->valid());
		$this->assertSame(' ', $spaceResult->values()['status']);
	}

	public function testFieldEmptyWhitespaceDefaultFillsBlankStrings(): void
	{
		$shape = new Shape();
		$shape->add('status', 'string')->empty(Blank::Whitespace)->default('draft');

		$emptyResult = $shape->validate(['status' => '']);
		$blankResult = $shape->validate(['status' => " \n\t"]);

		$this->assertTrue($emptyResult->valid());
		$this->assertSame('draft', $emptyResult->values()['status']);
		$this->assertTrue($blankResult->valid());
		$this->assertSame('draft', $blankResult->values()['status']);
	}

	public function testFieldEmptyListDefaultFillsEmptyList(): void
	{
		$shape = new Shape();
		$shape->add('items', 'list')->empty(Blank::List)->default(['draft']);

		$result = $shape->validate(['items' => []]);

		$this->assertTrue($result->valid());
		$this->assertSame(['draft'], $result->values()['items']);
	}

	public function testFieldPreparationRunsBeforeEmptyDefault(): void
	{
		$shape = new Shape();
		$shape
			->add('status', 'string')
			->empty(Blank::String)
			->default('draft')
			->prepare(static fn(mixed $value): string => trim((string) $value));

		$result = $shape->validate(['status' => '   ']);

		$this->assertTrue($result->valid());
		$this->assertSame('draft', $result->values()['status']);
	}

	public function testFieldEmptyNullOptionalOmitsNullAfterPreparation(): void
	{
		$called = false;
		$shape = new Shape();
		$shape
			->add('name', 'string')
			->empty(Blank::Null)
			->optional()
			->prepare(static function (mixed $value) use (&$called): mixed {
				$called = true;

				return $value;
			});

		$result = $shape->validate(['name' => null]);

		$this->assertTrue($result->valid());
		$this->assertTrue($called);
		$this->assertSame([], $result->values());
	}

	public function testOptionalFieldOmitsMissingWhenMissingIsNotEmpty(): void
	{
		$shape = new Shape();
		$shape->add('status', 'string')->empty(Blank::Null)->optional();

		$result = $shape->validate([]);

		$this->assertTrue($result->valid());
		$this->assertSame([], $result->values());
	}

	public function testFieldBlankWithoutDefaultAddsMissingError(): void
	{
		$shape = new Shape();
		$shape->add('title', 'string')->label('Title')->empty(Blank::String);

		$result = $shape->validate(['title' => '']);

		$this->assertFalse($result->valid());
		$this->assertSame('Title is required', $result->first('title'));
		$this->assertSame([], $result->values());
	}

	public function testPresentValueOverridesFieldEmptyDefault(): void
	{
		$shape = new Shape();
		$shape
			->add('status', 'string')
			->empty(Blank::Missing, Blank::Null)
			->default('draft');

		$result = $shape->validate(['status' => 'published']);

		$this->assertTrue($result->valid());
		$this->assertSame('published', $result->values()['status']);
	}

	public function testCoercerDefinedEmptyValueSkipsNormalRules(): void
	{
		$rule = new class implements Contract\Rule {
			public bool $called = false;

			public string $message {
				get => 'Should not run';
			}

			#[Override]
			public function validate(Contract\Value $value, string ...$args): Contract\Validation
			{
				$this->called = true;

				return Validation::invalid();
			}
		};

		$shape = new Shape();
		$shape->type('marker', self::emptyMarkerCoercer());
		$shape->rule('tracked', $rule);
		$shape->add('marker', 'marker')->rules('tracked');

		$result = $shape->validate(['marker' => 'empty']);

		$this->assertTrue($result->valid());
		$this->assertFalse($rule->called);
		$this->assertSame('empty', $result->values()['marker']);
	}

	public function testRequiredUsesCoercerDefinedEmptyValue(): void
	{
		$shape = new Shape();
		$shape->type('marker', self::emptyMarkerCoercer());
		$shape->add('marker', 'marker')->rules('required');

		$result = $shape->validate(['marker' => 'empty']);

		$this->assertFalse($result->valid());
		$this->assertSame('marker is required', $result->first('marker'));
	}

	public function testBooleanFalseRunsNormalRules(): void
	{
		$rule = new class implements Contract\Rule {
			public bool $called = false;

			public string $message {
				get => 'Must be true';
			}

			#[Override]
			public function validate(Contract\Value $value, string ...$args): Contract\Validation
			{
				$this->called = true;

				return Validation::invalid();
			}
		};

		$shape = new Shape();
		$shape->rule('must_be_true', $rule);
		$shape->add('enabled', 'bool')->rules('must_be_true');

		$result = $shape->validate(['enabled' => false]);

		$this->assertFalse($result->valid());
		$this->assertTrue($rule->called);
		$this->assertSame('Must be true', $result->first('enabled'));
	}

	public function testBooleanBlankStringFailsTypeValidation(): void
	{
		$shape = new Shape();
		$shape->add('enabled', 'bool');

		$result = $shape->validate(['enabled' => '']);

		$this->assertFalse($result->valid());
		$this->assertSame('enabled must be true or false', $result->first('enabled'));
	}

	public function testBooleanBlankStringCanUseRawEmptyDefault(): void
	{
		$shape = new Shape();
		$shape
			->add('enabled', 'bool')
			->empty(Blank::String)
			->default(false);

		$result = $shape->validate(['enabled' => '']);

		$this->assertTrue($result->valid());
		$this->assertSame(false, $result->values()['enabled']);
	}

	private static function emptyMarkerCoercer(): Contract\Coercer
	{
		return new class implements Contract\Coercer {
			public string $message {
				get => 'Invalid marker';
			}

			#[Override]
			public function coerce(
				mixed $pristine,
				CoercionMode $mode,
			): Contract\Coercion {
				$value = (string) $pristine;

				return new Coercion($value, $pristine, empty: $value === 'empty');
			}
		};
	}
}
