<?php

declare(strict_types=1);

namespace App\Infrastructure\Messenger;

use App\Infrastructure\GridStore\Exception\GridStoreException;
use App\Simulation\Exception\SimulationNotFoundException;
use App\Simulation\SimulationService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Exception\ORMException;
use LogicException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RunSimulationHandler
{
	public function __construct(
		private SimulationService $simulationService,
	) {
	}

	/**
	 * @throws SimulationNotFoundException
	 * @throws LogicException
	 * @throws ORMException
	 * @throws UniqueConstraintViolationException
	 * @throws GridStoreException
	 */
	public function __invoke(RunSimulationMessage $message): void
	{
		$this->simulationService->run(simulationId: $message->simulationId);
	}
}
