<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Shape;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
	public function getListData(): array
	{
		return [
			[
				'int' => 13,
				'email' => 'chuck@example.com',
				'single_shape' => [
					'inner_email' => 'test@example.com',
				],
				'list_shape' => [[
					'inner_int' => 23,
					'inner_email' => 'test@example.com',
				]],
			],
			[
				'int' => 73,
				'email' => 'chuck',
				'list_shape' => [
					[
						'inner_int' => 43,
						'inner_email' => 'test@example.com',
					],
				],
			],
			[ // the valid record
				'int' => 23,
				'text' => 'Text 23',
				'single_shape' => [
					'inner_int' => 97,
					'inner_email' => 'test@example.com',
				],
				'list_shape' => [[
					'inner_int' => 83,
					'inner_email' => 'test@example.com',
				]],
			],
			[
				'int' => 17,
				'text' => 'Text 2',
				'single_shape' => [
					'inner_int' => 23,
					'inner_email' => 'test INVALID example.com',
				],
				'list_shape' => [
					[
						'inner_int' => 'invalid',
						'inner_email' => 'example@example.com',
					],
					[
						'inner_int' => 29,
						'inner_email' => 'example@example.com',
					],
					[
						'inner_int' => '37',
						'inner_email' => 'example INVALID example.com',
					],
				],
			],
		];
	}

	public function getListShape(): Shape
	{
		return new class(title: 'List Root', list: true) extends Shape {
			protected function rules(): void
			{
				$this->add('int', 'int', 'required');
				$this->add('text', 'text', 'required');
				$this->add('email', 'text', 'email', 'minlen:10');
				$this->add(
					'single_shape',
					new SubShape(title: 'Single Sub'),
					'required',
				)->label('Single Shape');
				$this->add(
					'list_shape',
					new SubShape(title: 'List Sub', list: true),
				);
			}
		};
	}
}
