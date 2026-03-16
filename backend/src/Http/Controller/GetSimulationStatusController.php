<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\XmlResponseFactory;
use App\Simulation\Exception\SimulationNotFoundException;
use App\Simulation\SimulationService;
use DOMException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetSimulationStatusController
{
	public function __construct(
		private SimulationService $simulationService,
		private XmlResponseFactory $xmlResponseFactory,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @throws DOMException
	 * @throws InvalidArgumentException
	 */
	#[Route(path: '/api/simulations/{id}/status', methods: ['GET'])]
	public function __invoke(int $id): Response
	{
		try {
			$simulation = $this->simulationService->getSimulation(id: $id);
		} catch (SimulationNotFoundException) {
			$this->logger->warning(message: 'Simulation #{id} not found.', context: [
				'id' => $id,
			]);

			return $this->xmlResponseFactory->error(message: 'Simulation not found', statusCode: Response::HTTP_NOT_FOUND);
		}

		return $this->xmlResponseFactory->simulationStatus(
			id: (int) $simulation->getId(),
			status: $simulation->getStatus()
				->value,
			dimension: $simulation->getDimension(),
			speciesCount: $simulation->getSpeciesCount(),
			iterationsCount: $simulation->getIterationsCount(),
			createdAt: $simulation->getCreatedAt(),
			completedAt: $simulation->getCompletedAt(),
		);
	}
}
