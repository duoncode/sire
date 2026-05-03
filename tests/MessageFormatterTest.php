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

	public function testUsesFallbackMessage(): void
	{
		$formatter = new MessageFormatter([]);

		$message = $formatter->format(
			new Failure('type.slug', fallback: 'Invalid slug'),
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
