<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger;

final readonly class RunSimulationMessage
{
	public function __construct(
		public int $simulationId,
	) {
	}
}
