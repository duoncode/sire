<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\RuleParser;
use ValueError;

class RuleParserTest extends TestCase
{
	public function testParsesSimpleRule(): void
	{
		$parser = new RuleParser();

		$this->assertSame(
			['name' => 'required', 'args' => []],
			$parser->parse('required'),
		);
	}

	public function testParsesQuotedAndEscapedArguments(): void
	{
		$parser = new RuleParser();

		$this->assertSame(
			['name' => 'starts_with', 'args' => ['http://']],
			$parser->parse('starts_with:http\\://'),
		);

		$this->assertSame(
			['name' => 'starts_with', 'args' => ['http://']],
			$parser->parse('starts_with:"http://"'),
		);
	}

	public function testParsesRegexWithColonLikeLegacyDsl(): void
	{
		$parser = new RuleParser();

		$this->assertSame(
			[
				'name' => 'regex',
				'args' => ['/^[a-z', ']+', '$/'],
			],
			$parser->parse('regex:/^[a-z:]+:$/'),
		);
	}

	public function testThrowsOnInvalidDefinition(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('unclosed quote');

		$parser = new RuleParser();
		$parser->parse('in:"foo,bar');
	}

	public function testThrowsOnMissingRuleName(): void
	{
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('missing rule name');

		$parser = new RuleParser();
		$parser->parse(':10');
	}

	public function testParsesEmptyArgument(): void
	{
		$parser = new RuleParser();

		$this->assertSame(
			['name' => 'regex', 'args' => ['']],
			$parser->parse('regex:'),
		);
	}
}
