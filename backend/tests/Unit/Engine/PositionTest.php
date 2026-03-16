<?php

declare(strict_types=1);

namespace App\Tests\Unit\Engine;

use App\Engine\Position;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PositionTest extends TestCase
{
	#[Test]
	public function keyFormat(): void
	{
		$position = new Position(x: 3, y: 7);

		self::assertSame(expected: '3:7', actual: $position->key());
	}

	#[Test]
	public function negativeCoordinatesThrow(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new Position(x: -1, y: 0);
	}
}
