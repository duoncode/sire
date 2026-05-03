<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Result;
use Duon\Sire\Violation;

class ResultTest extends TestCase
{
	public function testViolationFromArray(): void
	{
		$violation = Violation::fromArray([
			'error' => 'Invalid value',
			'title' => 'Main',
			'level' => 2,
			'item' => 1,
			'field' => 'email',
			'label' => 'Email',
		]);

		$this->assertSame('Invalid value', $violation->error);
		$this->assertSame('Main', $violation->title);
		$this->assertSame(2, $violation->level);
		$this->assertSame(1, $violation->item);
		$this->assertSame('email', $violation->field);
		$this->assertSame('Email', $violation->label);
		$this->assertSame(
			[
				'error' => 'Invalid value',
				'title' => 'Main',
				'level' => 2,
				'item' => 1,
				'field' => 'email',
				'label' => 'Email',
			],
			$violation->toArray(),
		);
	}

	public function testResultErrors(): void
	{
		$result = new Result(
			false,
			'Main',
			['email' => ['Invalid value']],
			[
				new Violation('Invalid value', 'Main', 1, null, 'email', 'Email'),
				new Violation('Invalid nested', null, 2, null, 'other', 'Other'),
			],
			['email' => 'x'],
			['email' => 'x'],
		);

		$this->assertFalse($result->isValid());
		$this->assertCount(2, $result->violations());
		$this->assertSame(['email' => ['Invalid value']], $result->map());
		$this->assertSame(['email' => 'x'], $result->values());
		$this->assertSame(['email' => 'x'], $result->pristineValues());

		$errors = $result->errors();
		$this->assertFalse($errors['grouped']);
		$this->assertCount(2, $errors['errors']);

		$grouped = $result->errors(grouped: true);
		$this->assertTrue($grouped['grouped']);
		$this->assertCount(2, $grouped['errors']);
		$this->assertSame('Main', $grouped['errors'][0]['title']);
		$this->assertNull($grouped['errors'][1]['title']);
	}

	public function testResultValid(): void
	{
		$result = new Result(false, null, [], [], [], []);

		$this->assertTrue($result->isValid());
		$this->assertCount(0, $result->violations());
		$this->assertSame([], $result->map());
		$this->assertCount(0, $result->errors()['errors']);
	}

	public function testResultJsonSerializable(): void
	{
		$result = new Result(
			false,
			'Main',
			['email' => ['Invalid value']],
			[
				new Violation('Invalid value', 'Main', 1, null, 'email', 'Email'),
			],
			['email' => 'invalid'],
			['email' => 'invalid'],
		);

		$json = json_encode($result, JSON_THROW_ON_ERROR);
		$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

		$this->assertArrayHasKey('isValid', $data);
		$this->assertSame(false, $data['isValid']);
		$this->assertSame('Main', $data['title']);
		$this->assertSame('Invalid value', $data['violations'][0]['error']);
		$this->assertSame('invalid', $data['values']['email']);
		$this->assertSame('invalid', $data['pristineValues']['email']);
	}
}
