<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Contract\Shape as ShapeContract;
use Duon\Sire\Result;
use Duon\Sire\Shape;
use Override;

final class SubShape implements ShapeContract
{
	private Shape $shape;

	public function __construct(bool $list = false, ?string $title = null)
	{
		$this->shape = $list ? Shape::list() : new Shape();
		$this->shape->title($title);
		$this->shape->add('inner_int', 'int', 'required')->label('Int');
		$this->shape->add('inner_email', 'text', 'required', 'email')->label('Email');
	}

	#[Override]
	public function validate(array $data, int $level = 1): Result
	{
		return $this->shape->validate($data, $level);
	}
}
