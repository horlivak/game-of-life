<?php

declare(strict_types=1);

namespace App\Simulation\Exception;

use RuntimeException;

final class SimulationResultNotFoundException extends RuntimeException
{
	public static function withId(int $simulationId): self
	{
		return new self(message: sprintf('Result for simulation #%d not found in storage', $simulationId));
	}
}
