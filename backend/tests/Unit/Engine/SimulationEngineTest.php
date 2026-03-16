<?php

declare(strict_types=1);

namespace App\Tests\Unit\Engine;

use App\Engine\Cell;
use App\Engine\ConflictResolver;
use App\Engine\Grid;
use App\Engine\Position;
use App\Engine\Rule\ReproductionRule;
use App\Engine\Rule\StandardDeathRule;
use App\Engine\SimulationEngine;
use App\Engine\Species;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SimulationEngineTest extends TestCase
{
	private SimulationEngine $engine;

	protected function setUp(): void
	{
		$this->engine = new SimulationEngine(
			deathRules: [new StandardDeathRule()],
			birthRules: [new ReproductionRule()],
			conflictResolver: new ConflictResolver(),
		);
	}

	#[Test]
	public function isolatedCellDies(): void
	{
		$cell = new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'A'));
		$grid = new Grid(size: 5, cells: [
			$cell->position->key() => $cell,
		]);

		$result = $this->engine->tick(grid: $grid);

		self::assertCount(expectedCount: 0, haystack: $result->cells);
	}

	#[Test]
	public function blockPatternStable(): void
	{
		// 2x2 block is a still life pattern
		$grid = $this->createBlockGrid();

		$result = $this->engine->simulate(grid: $grid, iterations: 5);

		self::assertCount(expectedCount: 4, haystack: $result->cells);
		self::assertTrue(condition: $result->hasCellAt(position: new Position(x: 1, y: 1)));
		self::assertTrue(condition: $result->hasCellAt(position: new Position(x: 1, y: 2)));
		self::assertTrue(condition: $result->hasCellAt(position: new Position(x: 2, y: 1)));
		self::assertTrue(condition: $result->hasCellAt(position: new Position(x: 2, y: 2)));
	}

	#[Test]
	public function blinkerOscillator(): void
	{
		// Horizontal blinker: three in a row
		$grid = new Grid(size: 5, cells: [
			'1:2' => new Cell(position: new Position(x: 1, y: 2), species: new Species(type: 'A')),
			'2:2' => new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'A')),
			'3:2' => new Cell(position: new Position(x: 3, y: 2), species: new Species(type: 'A')),
		]);

		// After 1 tick → vertical
		$tick1 = $this->engine->tick(grid: $grid);
		self::assertCount(expectedCount: 3, haystack: $tick1->cells);
		self::assertTrue(condition: $tick1->hasCellAt(position: new Position(x: 2, y: 1)));
		self::assertTrue(condition: $tick1->hasCellAt(position: new Position(x: 2, y: 2)));
		self::assertTrue(condition: $tick1->hasCellAt(position: new Position(x: 2, y: 3)));

		// After 2 ticks → back to horizontal
		$tick2 = $this->engine->tick(grid: $tick1);
		self::assertCount(expectedCount: 3, haystack: $tick2->cells);
		self::assertTrue(condition: $tick2->hasCellAt(position: new Position(x: 1, y: 2)));
		self::assertTrue(condition: $tick2->hasCellAt(position: new Position(x: 2, y: 2)));
		self::assertTrue(condition: $tick2->hasCellAt(position: new Position(x: 3, y: 2)));
	}

	#[Test]
	public function simulateZeroIterations(): void
	{
		$grid = $this->createBlockGrid();

		$result = $this->engine->simulate(grid: $grid, iterations: 0);

		self::assertCount(expectedCount: 4, haystack: $result->cells);
	}

	#[Test]
	public function simulateMultipleIterations(): void
	{
		// Block pattern should be unchanged after any number of iterations
		$grid = $this->createBlockGrid();

		$result = $this->engine->simulate(grid: $grid, iterations: 10);

		self::assertCount(expectedCount: 4, haystack: $result->cells);
	}

	private function createBlockGrid(): Grid
	{
		return new Grid(size: 5, cells: [
			'1:1' => new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'A')),
			'1:2' => new Cell(position: new Position(x: 1, y: 2), species: new Species(type: 'A')),
			'2:1' => new Cell(position: new Position(x: 2, y: 1), species: new Species(type: 'A')),
			'2:2' => new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'A')),
		]);
	}
}
