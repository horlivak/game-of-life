<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\XmlResponseFactory;
use App\Infrastructure\Xml\Exception\XmlSerializationException;
use App\Infrastructure\Xml\XmlSerializer;
use App\Simulation\Exception\SimulationNotCompletedException;
use App\Simulation\Exception\SimulationNotFoundException;
use App\Simulation\Exception\SimulationResultNotFoundException;
use App\Simulation\SimulationService;
use App\Simulation\SimulationStatus;
use DOMException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final readonly class GetSimulationResultController
{
	public function __construct(
		private SimulationService $simulationService,
		private XmlSerializer $xmlSerializer,
		private XmlResponseFactory $xmlResponseFactory,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @throws DOMException
	 * @throws InvalidArgumentException
	 * @throws XmlSerializationException
	 */
	#[Route(path: '/api/simulations/{id}/result', methods: ['GET'])]
	public function __invoke(int $id): Response
	{
		try {
			$result = $this->simulationService->getResult(simulationId: $id);
		} catch (SimulationNotFoundException) {
			$this->logger->warning(message: 'Simulation #{id} not found.', context: [
				'id' => $id,
			]);

			return $this->xmlResponseFactory->error(message: 'Simulation not found', statusCode: Response::HTTP_NOT_FOUND);
		} catch (SimulationNotCompletedException $exception) {
			if ($exception->status === SimulationStatus::Failed) {
				return $this->xmlResponseFactory->error(
					message: 'Simulation failed',
					statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
				);
			}

			return $this->xmlResponseFactory->error(
				message: 'Simulation not completed yet',
				statusCode: Response::HTTP_ACCEPTED,
			);
		} catch (SimulationResultNotFoundException) {
			return $this->xmlResponseFactory->error(
				message: 'Simulation result not found',
				statusCode: Response::HTTP_NOT_FOUND,
			);
		} catch (Throwable $throwable) {
			$this->logger->error(message: 'Failed to load simulation #{id} result.', context: [
				'id' => $id,
				'exception' => $throwable,
			]);

			return $this->xmlResponseFactory->error(
				message: 'Internal server error',
				statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
			);
		}

		return $this->xmlResponseFactory->xml(xmlContent: $this->xmlSerializer->render(result: $result));
	}
}
