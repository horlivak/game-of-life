<?php

declare(strict_types=1);

namespace App\Simulation\Exception;

use App\Simulation\SimulationStatus;
use RuntimeException;

final class SimulationNotCompletedException extends RuntimeException
{
	public function __construct(
		string $message,
		public readonly SimulationStatus $status,
	) {
		parent::__construct(message: $message);
	}

	public static function withStatus(int $simulationId, SimulationStatus $status): self
	{
		return new self(
			message: sprintf('Simulation #%d is not completed (current status: %s).', $simulationId, $status->value),
			status: $status,
		);
	}
}
