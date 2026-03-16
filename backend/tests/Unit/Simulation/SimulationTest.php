<?php

declare(strict_types=1);

namespace App\Tests\Unit\Simulation;

use App\Simulation\Simulation;
use App\Simulation\SimulationStatus;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SimulationTest extends TestCase
{
	#[Test]
	public function newSimulationIsPending(): void
	{
		$simulation = $this->createSimulation();

		self::assertSame(expected: SimulationStatus::Pending, actual: $simulation->getStatus());
		self::assertNull(actual: $simulation->getCompletedAt());
	}

	#[Test]
	public function getters(): void
	{
		$simulation = $this->createSimulation();

		self::assertSame(expected: 10, actual: $simulation->getDimension());
		self::assertSame(expected: 2, actual: $simulation->getSpeciesCount());
		self::assertSame(expected: 5, actual: $simulation->getIterationsCount());
		self::assertSame(expected: ['A', 'B'], actual: $simulation->getSpeciesTypes());
		self::assertSame(expected: date(format: 'Y-m-d'), actual: $simulation->getCreatedAt()->format(format: 'Y-m-d'));
		self::assertSame(expected: 1, actual: $simulation->getVersion());
	}

	#[Test]
	public function pendingToRunning(): void
	{
		$simulation = $this->createSimulation();
		$simulation->markRunning();

		self::assertSame(expected: SimulationStatus::Running, actual: $simulation->getStatus());
	}

	#[Test]
	public function runningToCompleted(): void
	{
		$simulation = $this->createSimulation();
		$simulation->markRunning();
		$simulation->markCompleted();

		self::assertSame(expected: SimulationStatus::Completed, actual: $simulation->getStatus());
		self::assertNotNull(actual: $simulation->getCompletedAt());
	}

	#[Test]
	public function runningToFailed(): void
	{
		$simulation = $this->createSimulation();
		$simulation->markRunning();
		$simulation->markFailed();

		self::assertSame(expected: SimulationStatus::Failed, actual: $simulation->getStatus());
		self::assertNotNull(actual: $simulation->getCompletedAt());
	}

	#[Test]
	public function pendingToFailed(): void
	{
		$simulation = $this->createSimulation();
		$simulation->markFailed();

		self::assertSame(expected: SimulationStatus::Failed, actual: $simulation->getStatus());
		self::assertNotNull(actual: $simulation->getCompletedAt());
	}

	#[Test]
	public function cannotMarkCompletedFromPending(): void
	{
		$simulation = $this->createSimulation();

		$this->expectException(LogicException::class);
		$simulation->markCompleted();
	}

	#[Test]
	public function cannotMarkRunningFromCompleted(): void
	{
		$simulation = $this->createSimulation();
		$simulation->markRunning();
		$simulation->markCompleted();

		$this->expectException(LogicException::class);
		$simulation->markRunning();
	}

	#[Test]
	public function cannotMarkFailedFromCompleted(): void
	{
		$simulation = $this->createSimulation();
		$simulation->markRunning();
		$simulation->markCompleted();

		$this->expectException(LogicException::class);
		$simulation->markFailed();
	}

	#[Test]
	public function cannotMarkRunningFromFailed(): void
	{
		$simulation = $this->createSimulation();
		$simulation->markRunning();
		$simulation->markFailed();

		$this->expectException(LogicException::class);
		$simulation->markRunning();
	}

	private function createSimulation(): Simulation
	{
		return new Simulation(dimension: 10, speciesCount: 2, iterationsCount: 5, speciesTypes: ['A', 'B']);
	}
}
