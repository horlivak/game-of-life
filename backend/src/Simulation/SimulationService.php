<?php

declare(strict_types=1);

namespace App\Simulation;

use App\Engine\Cell;
use App\Engine\ConflictResolverInterface;
use App\Engine\Grid;
use App\Engine\SimulationEngine;
use App\Infrastructure\GridStore\Exception\GridStoreException;
use App\Infrastructure\GridStore\GridStoreInterface;
use App\Infrastructure\Messenger\RunSimulationMessage;
use App\Simulation\Exception\SimulationNotCompletedException;
use App\Simulation\Exception\SimulationNotFoundException;
use App\Simulation\Exception\SimulationPersistenceException;
use App\Simulation\Exception\SimulationResultNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final readonly class SimulationService
{
	public function __construct(
		private SimulationRepository $repository,
		private GridStoreInterface $gridStore,
		private SimulationEngine $engine,
		private ConflictResolverInterface $conflictResolver,
		private MessageBusInterface $messageBus,
		private EntityManagerInterface $entityManager,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param list<Cell> $cells
	 * @param list<string> $speciesTypes
	 *
	 * @throws SimulationPersistenceException
	 * @throws InvalidArgumentException
	 * @throws ORMException
	 * @throws UniqueConstraintViolationException
	 * @throws ExceptionInterface
	 * @throws LogicException
	 */
	public function create(int $dimension, array $cells, int $iterations, array $speciesTypes): Simulation
	{
		$resolved = $this->conflictResolver->resolve(cells: $cells);
		$grid = Grid::fromCells(size: $dimension, cells: $resolved);

		$simulation = new Simulation(
			dimension: $grid->size,
			speciesCount: count(value: $speciesTypes),
			iterationsCount: $iterations,
			speciesTypes: $speciesTypes,
		);

		$this->entityManager->beginTransaction();

		try {
			$this->repository->save(simulation: $simulation);

			$simulationId = $simulation->getId();

			if ($simulationId === null) {
				throw SimulationPersistenceException::idNotAssigned();
			}

			$this->gridStore->save(simulationId: $simulationId, grid: $grid);
			$this->entityManager->commit();
		} catch (Throwable $throwable) {
			$this->entityManager->rollback();

			throw $throwable;
		}

		try {
			$this->messageBus->dispatch(message: new RunSimulationMessage(simulationId: $simulationId));
		} catch (ExceptionInterface $exception) {
			$this->logger->error(message: 'Failed to dispatch simulation #{id}.', context: [
				'id' => $simulationId,
				'exception' => $exception,
			]);

			$simulation->markFailed();
			$this->repository->save(simulation: $simulation);

			throw $exception;
		}

		return $simulation;
	}

	/**
	 * @throws SimulationNotFoundException
	 * @throws ORMException
	 * @throws UniqueConstraintViolationException
	 * @throws LogicException
	 * @throws GridStoreException
	 */
	public function run(int $simulationId): void
	{
		$simulation = $this->repository->get(id: $simulationId);

		try {
			$simulation->markRunning();
			$this->repository->save(simulation: $simulation);
		} catch (OptimisticLockException $optimisticLockException) {
			$this->logger->warning(message: 'Simulation #{id} already picked up by another worker.', context: [
				'id' => $simulationId,
				'exception' => $optimisticLockException,
			]);

			return;
		}

		$grid = $this->gridStore->load(simulationId: $simulationId);

		if ($grid === null) {
			$simulation->markFailed();
			$this->repository->save(simulation: $simulation);

			return;
		}

		try {
			$resultGrid = $this->engine->simulate(grid: $grid, iterations: $simulation->getIterationsCount());
			$this->gridStore->save(simulationId: $simulationId, grid: $resultGrid);
			$simulation->markCompleted();
		} catch (Throwable $throwable) {
			$this->logger->error(message: 'Simulation #{id} failed: {message}', context: [
				'id' => $simulationId,
				'message' => $throwable->getMessage(),
				'exception' => $throwable,
			]);

			$simulation->markFailed();
			$this->repository->save(simulation: $simulation);

			return;
		}

		$this->repository->save(simulation: $simulation);
	}

	/**
	 * @throws SimulationNotFoundException
	 */
	public function getSimulation(int $id): Simulation
	{
		return $this->repository->get(id: $id);
	}

	/**
	 * @throws SimulationNotFoundException
	 * @throws SimulationNotCompletedException
	 * @throws SimulationResultNotFoundException
	 * @throws GridStoreException
	 */
	public function getResult(int $simulationId): SimulationResult
	{
		$simulation = $this->repository->get(id: $simulationId);

		if ($simulation->getStatus() !== SimulationStatus::Completed) {
			throw SimulationNotCompletedException::withStatus(
				simulationId: $simulationId,
				status: $simulation->getStatus(),
			);
		}

		$grid = $this->gridStore->load(simulationId: $simulationId);

		if ($grid === null) {
			throw SimulationResultNotFoundException::withId(simulationId: $simulationId);
		}

		return new SimulationResult(
			grid: $grid,
			speciesTypes: $simulation->getSpeciesTypes(),
			iterationsCount: $simulation->getIterationsCount(),
		);
	}
}
