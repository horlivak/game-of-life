<?php

declare(strict_types=1);

namespace App\Http\Request;

use App\Engine\Cell;
use App\Engine\SimulationEngine;
use App\Simulation\SimulationInput;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateSimulationRequest
{
	private const int MAX_DIMENSION = 1000;

	/**
	 * @param list<Cell> $cells
	 * @param list<string> $speciesTypes
	 */
	public function __construct(
		#[Assert\Positive]
		#[Assert\LessThanOrEqual(value: self::MAX_DIMENSION)]
		public int $dimension,
		#[Assert\Count(min: 1)]
		public array $cells,
		#[Assert\Positive]
		#[Assert\LessThanOrEqual(value: SimulationEngine::MAX_ITERATIONS)]
		public int $iterations,
		#[Assert\Count(min: 1)]
		#[Assert\All(constraints: [new Assert\NotBlank()])]
		public array $speciesTypes,
	) {
	}

	public static function fromSimulationInput(SimulationInput $input): self
	{
		return new self(
			dimension: $input->dimension,
			cells: $input->cells,
			iterations: $input->iterations,
			speciesTypes: $input->speciesTypes,
		);
	}
}
