<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Issue;
use Duon\Sire\Result;

class ResultTest extends TestCase
{
	public function testIssueOutput(): void
	{
		$issue = new Issue(['users', 1, 'email'], 'rule.email', 'Invalid value', [
			'arg1' => 'extra',
		]);

		$this->assertSame(['users', 1, 'email'], $issue->path);
		$this->assertSame('rule.email', $issue->code);
		$this->assertSame('Invalid value', $issue->message);
		$this->assertSame(['arg1' => 'extra'], $issue->params);
		$this->assertSame(
			[
				'path' => ['users', 1, 'email'],
				'code' => 'rule.email',
				'message' => 'Invalid value',
				'params' => ['arg1' => 'extra'],
			],
			$issue->toArray(),
		);
	}

	public function testResultIssuesAndMessages(): void
	{
		$result = new Result(
			[
				new Issue(['email'], 'rule.email', 'Invalid value'),
				new Issue(['profile', 'name'], 'missing', 'Name is required'),
			],
			['email' => 'x'],
		);

		$this->assertFalse($result->valid());
		$this->assertCount(2, $result->issues());
		$this->assertSame(['Invalid value'], $result->messages('email'));
		$this->assertSame('Invalid value', $result->first('email'));
		$this->assertTrue($result->has('profile.name'));
		$this->assertFalse($result->has('missing'));
		$this->assertSame(['email' => 'x'], $result->values());
	}

	public function testResultPathHelpersAcceptRootAndIntegerPaths(): void
	{
		$result = new Result(
			[
				new Issue([], 'form', 'Form error'),
				new Issue([0], 'row', 'First row error'),
				new Issue(['items', 0, 'name'], 'item.name', 'Name error'),
			],
			[],
		);

		$this->assertSame(['Form error'], $result->messages(''));
		$this->assertSame('Form error', $result->first(''));
		$this->assertTrue($result->has(''));
		$this->assertSame(['First row error'], $result->messages(0));
		$this->assertSame('First row error', $result->first(0));
		$this->assertTrue($result->has(0));
		$this->assertSame(['Name error'], $result->messages('items.0.name'));
	}

	public function testResultValid(): void
	{
		$result = new Result([], []);

		$this->assertTrue($result->valid());
		$this->assertCount(0, $result->issues());
		$this->assertSame([], $result->messages('email'));
		$this->assertNull($result->first('email'));
		$this->assertFalse($result->has('email'));
	}

	public function testResultJsonSerializable(): void
	{
		$result = new Result(
			[
				new Issue(['email'], 'rule.email', 'Invalid value'),
			],
			['email' => 'invalid'],
		);

		$json = json_encode($result, JSON_THROW_ON_ERROR);
		$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

		$this->assertArrayHasKey('valid', $data);
		$this->assertSame(false, $data['valid']);
		$this->assertSame(['email'], $data['issues'][0]['path']);
		$this->assertSame('rule.email', $data['issues'][0]['code']);
		$this->assertSame('Invalid value', $data['issues'][0]['message']);
		$this->assertArrayNotHasKey('values', $data);
	}
}
