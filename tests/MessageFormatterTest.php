<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Failure;
use Duon\Sire\MessageFormatter;

class MessageFormatterTest extends TestCase
{
	public function testFormatsConfiguredMessage(): void
	{
		$formatter = new MessageFormatter([
			'type.int' => '%1$s/%2$s/%3$s/%4$s',
		]);

		$message = $formatter->format(
			new Failure('type.int', ['extra']),
			'Age',
			'age',
			'raw',
		);

		$this->assertSame('Age/age/raw/extra', $message);
	}

	public function testFormatsDefaultArguments(): void
	{
		$formatter = new MessageFormatter([
			'validator.min' => '{label} must be at least {arg1}',
		]);

		$message = $formatter->format(
			Failure::invalid(),
			'Age',
			'age',
			'raw',
			'validator.min',
			'Fallback',
			['18'],
		);

		$this->assertSame('Age must be at least 18', $message);
	}

	public function testFormatsNamedMessage(): void
	{
		$formatter = new MessageFormatter([
			'type.int' => 'Field {field} with label "{label}" and original value \'{value}\' and failure args {arg1} {arg2}',
		]);

		$message = $formatter->format(
			Failure::key('type.int', 'first', 'second'),
			'Age',
			'age',
			'raw',
		);

		$this->assertSame(
			'Field age with label "Age" and original value \'raw\' and failure args first second',
			$message,
		);
	}

	public function testEscapesNamedBraces(): void
	{
		$formatter = new MessageFormatter([
			'type.int' => 'Use {{field}} for {field} and {{arg1}} for {arg1}',
		]);

		$message = $formatter->format(
			Failure::key('type.int', 'extra'),
			'Age',
			'age',
			'raw',
		);

		$this->assertSame('Use {field} for age and {arg1} for extra', $message);
	}

	public function testLeavesUnknownNamedPlaceholders(): void
	{
		$formatter = new MessageFormatter([
			'type.int' => 'Unknown {missing} {arg2}',
		]);

		$message = $formatter->format(
			Failure::key('type.int', 'extra'),
			'Age',
			'age',
			'raw',
		);

		$this->assertSame('Unknown {missing} {arg2}', $message);
	}

	public function testFormatsSprintfMessageWithUnknownBraces(): void
	{
		$formatter = new MessageFormatter([
			'type.int' => '%1$s must match {a,b}',
		]);

		$message = $formatter->format(
			new Failure('type.int'),
			'Age',
			'age',
			'raw',
		);

		$this->assertSame('Age must match {a,b}', $message);
	}

	public function testUsesDefaultMessageKey(): void
	{
		$formatter = new MessageFormatter([
			'type.int' => '%1$s must be numeric',
		]);

		$message = $formatter->format(
			Failure::invalid(fallback: 'Invalid'),
			'Age',
			'age',
			'raw',
			'type.int',
		);

		$this->assertSame('Age must be numeric', $message);
	}

	public function testUsesFallbackMessage(): void
	{
		$formatter = new MessageFormatter([]);

		$message = $formatter->format(
			Failure::invalid(fallback: 'Invalid slug'),
			'Slug',
			'slug',
			'Raw Value',
		);

		$this->assertSame('Invalid slug', $message);
	}

	public function testUsesGenericMessage(): void
	{
		$formatter = new MessageFormatter([]);

		$message = $formatter->format(
			new Failure('type.unknown'),
			'Field',
			'field',
			'value',
		);

		$this->assertSame('Invalid value', $message);
	}
}
