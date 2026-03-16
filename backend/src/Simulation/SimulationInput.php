<?php

declare(strict_types=1);

namespace App\Simulation;

use App\Engine\Cell;

final readonly class SimulationInput
{
	/**
	 * @param list<Cell> $cells
	 * @param list<string> $speciesTypes
	 */
	public function __construct(
		public int $dimension,
		public array $cells,
		public int $iterations,
		public array $speciesTypes,
	) {
	}
}
