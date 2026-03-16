<?php

declare(strict_types=1);

namespace App\Simulation;

use App\Engine\Grid;

final readonly class SimulationResult
{
	/**
	 * @param list<string> $speciesTypes
	 */
	public function __construct(
		public Grid $grid,
		public array $speciesTypes,
		public int $iterationsCount,
	) {
	}
}
