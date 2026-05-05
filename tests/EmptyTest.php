<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Blank;
use Duon\Sire\Shape;
use ValueError;

class EmptyTest extends TestCase
{
	public function testRejectsInvalidBlank(): void
	{
		$this->expectException(ValueError::class);

		new Shape()->add('name', 'text')->empty('blank');
	}

	public function testAcceptsStringBlank(): void
	{
		$shape = new Shape();
		$shape->add('name', 'text')->empty('missing', Blank::Null);

		$result = $shape->validate(['name' => 'Ada']);

		$this->assertTrue($result->isValid());
		$this->assertSame('Ada', $result->values()['name']);
	}

	public function testFieldDefaultValueDoesNotFillNullByDefault(): void
	{
		$shape = new Shape();
		$shape->add('status', 'text')->label('Status')->default('draft');

		$result = $shape->validate(['status' => null]);

		$this->assertFalse($result->isValid());
		$this->assertSame('Status must not be null', $result->first('status'));
		$this->assertNull($result->values()['status']);
	}

	public function testFieldEmptyNullDefaultFillsNullAndMissingValues(): void
	{
		$shape = new Shape();
		$shape
			->add('status', 'text')
			->empty(Blank::Missing, Blank::Null)
			->default('draft');

		$nullResult = $shape->validate(['status' => null]);
		$missingResult = $shape->validate([]);

		$this->assertTrue($nullResult->isValid());
		$this->assertSame('draft', $nullResult->values()['status']);
		$this->assertTrue($missingResult->isValid());
		$this->assertSame('draft', $missingResult->values()['status']);
	}

	public function testFieldEmptyNullDefaultDoesNotFillMissingWithoutMissingEmpty(): void
	{
		$shape = new Shape();
		$shape->add('status', 'text')->empty(Blank::Null)->default('draft');

		$result = $shape->validate([]);

		$this->assertFalse($result->isValid());
		$this->assertSame('status is required', $result->first('status'));
		$this->assertSame([], $result->values());
	}

	public function testFieldEmptyStringDefaultMatchesExactString(): void
	{
		$shape = new Shape();
		$shape->add('status', 'text')->empty(Blank::String)->default('draft');

		$emptyResult = $shape->validate(['status' => '']);
		$spaceResult = $shape->validate(['status' => ' ']);

		$this->assertTrue($emptyResult->isValid());
		$this->assertSame('draft', $emptyResult->values()['status']);
		$this->assertTrue($spaceResult->isValid());
		$this->assertSame(' ', $spaceResult->values()['status']);
	}

	public function testFieldEmptyWhitespaceDefaultFillsBlankStrings(): void
	{
		$shape = new Shape();
		$shape->add('status', 'text')->empty(Blank::Whitespace)->default('draft');

		$emptyResult = $shape->validate(['status' => '']);
		$blankResult = $shape->validate(['status' => " \n\t"]);

		$this->assertTrue($emptyResult->isValid());
		$this->assertSame('draft', $emptyResult->values()['status']);
		$this->assertTrue($blankResult->isValid());
		$this->assertSame('draft', $blankResult->values()['status']);
	}

	public function testFieldEmptyListDefaultFillsEmptyList(): void
	{
		$shape = new Shape();
		$shape->add('items', 'list')->empty(Blank::List)->default(['draft']);

		$result = $shape->validate(['items' => []]);

		$this->assertTrue($result->isValid());
		$this->assertSame(['draft'], $result->values()['items']);
	}

	public function testFieldEmptyNullOptionalOmitsNullWithoutPreparation(): void
	{
		$called = false;
		$shape = new Shape();
		$shape
			->add('name', 'text')
			->empty(Blank::Null)
			->optional()
			->prepare(static function (mixed $value) use (&$called): mixed {
				$called = true;

				return $value;
			});

		$result = $shape->validate(['name' => null]);

		$this->assertTrue($result->isValid());
		$this->assertFalse($called);
		$this->assertSame([], $result->values());
	}

	public function testOptionalFieldOmitsMissingWhenMissingIsNotEmpty(): void
	{
		$shape = new Shape();
		$shape->add('status', 'text')->empty(Blank::Null)->optional();

		$result = $shape->validate([]);

		$this->assertTrue($result->isValid());
		$this->assertSame([], $result->values());
	}

	public function testFieldBlankWithoutDefaultAddsMissingError(): void
	{
		$shape = new Shape();
		$shape->add('title', 'text')->label('Title')->empty(Blank::String);

		$result = $shape->validate(['title' => '']);

		$this->assertFalse($result->isValid());
		$this->assertSame('Title is required', $result->first('title'));
		$this->assertSame([], $result->values());
	}

	public function testPresentValueOverridesFieldEmptyDefault(): void
	{
		$shape = new Shape();
		$shape
			->add('status', 'text')
			->empty(Blank::Missing, Blank::Null)
			->default('draft');

		$result = $shape->validate(['status' => 'published']);

		$this->assertTrue($result->isValid());
		$this->assertSame('published', $result->values()['status']);
	}
}
