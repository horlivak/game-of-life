<?php

declare(strict_types=1);

namespace App\Engine;

final readonly class Cell
{
	public function __construct(
		public Position $position,
		public Species $species,
	) {
	}
}
