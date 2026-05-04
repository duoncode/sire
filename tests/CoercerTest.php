<?php

declare(strict_types=1);

namespace Duon\Sire\Tests;

use Duon\Sire\Coercer\Boolean;
use Duon\Sire\Coercer\FloatingPoint;
use Duon\Sire\Coercer\Integer;
use Duon\Sire\Coercer\Sequence;
use Duon\Sire\Coercer\Text;

class CoercerTest extends TestCase
{
	public function testBuiltInCoercersExposeMessages(): void
	{
		$this->assertSame('Invalid boolean', new Boolean()->message);
		$this->assertSame('Invalid number', new FloatingPoint()->message);
		$this->assertSame('Invalid number', new Integer()->message);
		$this->assertSame('Invalid list', new Sequence()->message);
		$this->assertSame('Invalid text', new Text()->message);
	}
}
