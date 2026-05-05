<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Contract;
use Duon\Sire\Result;
use Duon\Sire\Shape;
use Override;

final class SubShape implements Contract\Validator
{
	private Shape $shape;

	public function __construct(bool $list = false)
	{
		$this->shape = $list ? Shape::list() : new Shape();
		$this->shape->add('inner_int', 'int', 'required')->label('Int');
		$this->shape->add('inner_email', 'text', 'required', 'email')->label('Email');
	}

	#[Override]
	public function validate(array $data): Result
	{
		return $this->shape->validate($data);
	}
}
