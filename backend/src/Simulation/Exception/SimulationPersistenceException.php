<?php

declare(strict_types=1);

namespace App\Simulation\Exception;

use RuntimeException;

final class SimulationPersistenceException extends RuntimeException
{
	public static function idNotAssigned(): self
	{
		return new self(message: 'Failed to create simulation: ID was not assigned after persist');
	}
}
