<?php

declare(strict_types=1);

namespace App\Simulation\Exception;

use RuntimeException;

final class SimulationNotFoundException extends RuntimeException
{
	public static function withId(int $id): self
	{
		return new self(message: sprintf('Simulation #%d not found', $id));
	}
}
