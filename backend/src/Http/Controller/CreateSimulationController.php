<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Http\Request\CreateSimulationRequest;
use App\Http\XmlResponseFactory;
use App\Infrastructure\Xml\Exception\XmlDeserializationException;
use App\Infrastructure\Xml\XmlDeserializer;
use App\Simulation\SimulationService;
use DOMException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

final readonly class CreateSimulationController
{
	public function __construct(
		private SimulationService $simulationService,
		private ValidatorInterface $validator,
		private XmlDeserializer $xmlDeserializer,
		private XmlResponseFactory $xmlResponseFactory,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @throws DOMException
	 * @throws InvalidArgumentException
	 */
	#[Route(path: '/api/simulations', methods: ['POST'])]
	public function __invoke(Request $request): Response
	{
		try {
			$input = $this->xmlDeserializer->deserialize(xml: $request->getContent());
			$simulationRequest = CreateSimulationRequest::fromSimulationInput(input: $input);
		} catch (XmlDeserializationException | InvalidArgumentException $exception) {
			$this->logger->warning(message: 'Failed to parse simulation XML request.', context: [
				'exception' => $exception,
			]);

			return $this->xmlResponseFactory->error(message: 'Invalid XML', statusCode: Response::HTTP_BAD_REQUEST);
		}

		$violations = $this->validator->validate(value: $simulationRequest);

		if ($violations->count() > 0) {
			$errors = [];
			foreach ($violations as $violation) {
				$errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
			}

			$this->logger->warning(message: 'Simulation request validation failed.', context: [
				'errors' => $errors,
			]);

			return $this->xmlResponseFactory->errors(errors: $errors, statusCode: Response::HTTP_BAD_REQUEST);
		}

		try {
			$simulation = $this->simulationService->create(
				dimension: $simulationRequest->dimension,
				cells: $simulationRequest->cells,
				iterations: $simulationRequest->iterations,
				speciesTypes: $simulationRequest->speciesTypes,
			);
		} catch (InvalidArgumentException $exception) {
			$this->logger->warning(message: 'Invalid simulation parameters.', context: [
				'exception' => $exception,
			]);

			return $this->xmlResponseFactory->error(
				message: 'Invalid simulation parameters',
				statusCode: Response::HTTP_BAD_REQUEST,
			);
		} catch (Throwable $throwable) {
			$this->logger->error(message: 'Failed to create simulation.', context: [
				'exception' => $throwable,
			]);

			return $this->xmlResponseFactory->error(
				message: 'Internal server error',
				statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
			);
		}

		$simulationId = $simulation->getId();

		$this->logger->info(message: 'Simulation #{id} created.', context: [
			'id' => $simulationId,
			'dimension' => $simulationRequest->dimension,
			'iterations' => $simulationRequest->iterations,
			'organisms' => count(value: $simulationRequest->cells),
		]);

		/** @var int $simulationId ID is guaranteed after successful persist */
		return $this->xmlResponseFactory->simulationCreated(id: $simulationId, status: $simulation->getStatus() ->value);
	}
}
