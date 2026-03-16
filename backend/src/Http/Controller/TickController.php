<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Engine\ConflictResolverInterface;
use App\Engine\Grid;
use App\Engine\SimulationEngine;
use App\Http\XmlResponseFactory;
use App\Infrastructure\Xml\Exception\XmlDeserializationException;
use App\Infrastructure\Xml\Exception\XmlSerializationException;
use App\Infrastructure\Xml\XmlDeserializer;
use App\Infrastructure\Xml\XmlSerializer;
use App\Simulation\SimulationResult;
use DOMException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class TickController
{
	public function __construct(
		private SimulationEngine $engine,
		private XmlDeserializer $xmlDeserializer,
		private XmlSerializer $xmlSerializer,
		private XmlResponseFactory $xmlResponseFactory,
		private ConflictResolverInterface $conflictResolver,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @throws DOMException
	 * @throws InvalidArgumentException
	 * @throws XmlSerializationException
	 */
	#[Route(path: '/api/tick', methods: ['POST'])]
	public function __invoke(Request $request): Response
	{
		try {
			$input = $this->xmlDeserializer->deserialize(xml: $request->getContent());
		} catch (XmlDeserializationException | InvalidArgumentException $exception) {
			$this->logger->warning(message: 'Failed to parse tick XML request.', context: [
				'exception' => $exception,
			]);

			return $this->xmlResponseFactory->error(message: 'Invalid XML', statusCode: Response::HTTP_BAD_REQUEST);
		}

		$resolved = $this->conflictResolver->resolve(cells: $input->cells);
		$grid = Grid::fromCells(size: $input->dimension, cells: $resolved);

		try {
			$newGrid = $this->engine->tick(grid: $grid);
		} catch (InvalidArgumentException $invalidArgumentException) {
			$this->logger->warning(message: 'Tick failed due to invalid parameters.', context: [
				'dimension' => $input->dimension,
				'exception' => $invalidArgumentException,
			]);

			return $this->xmlResponseFactory->error(
				message: 'Invalid simulation parameters',
				statusCode: Response::HTTP_BAD_REQUEST,
			);
		}

		$result = new SimulationResult(grid: $newGrid, speciesTypes: $input->speciesTypes, iterationsCount: 1);

		return $this->xmlResponseFactory->xml(xmlContent: $this->xmlSerializer->render(result: $result));
	}
}
