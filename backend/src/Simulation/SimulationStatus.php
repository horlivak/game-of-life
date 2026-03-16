<?php

declare(strict_types=1);

namespace App\Simulation;

enum SimulationStatus: string
{
	case Pending = 'pending';
	case Running = 'running';
	case Completed = 'completed';
	case Failed = 'failed';
}
