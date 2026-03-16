<?php

declare(strict_types=1);

namespace App\Engine;

use InvalidArgumentException;

final readonly class Position
{
	/**
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		public int $x,
		public int $y,
	) {
		if ($x < 0 || $y < 0) {
			throw new InvalidArgumentException(
				message: sprintf('Position coordinates must be non-negative, got (%d, %d).', $x, $y),
			);
		}
	}

	public function key(): string
	{
		return $this->x . ':' . $this->y;
	}
}
