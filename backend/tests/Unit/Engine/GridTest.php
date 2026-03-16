<?php

declare(strict_types=1);

namespace App\Tests\Unit\Engine;

use App\Engine\Cell;
use App\Engine\Grid;
use App\Engine\Position;
use App\Engine\Species;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GridTest extends TestCase
{
	#[Test]
	public function emptyGrid(): void
	{
		$grid = new Grid(size: 5);
		$position = new Position(x: 2, y: 2);

		self::assertNull(actual: $grid->getCellAt(position: $position));
		self::assertFalse(condition: $grid->hasCellAt(position: $position));
	}

	#[Test]
	public function getCellAt(): void
	{
		$position = new Position(x: 1, y: 1);
		$cell = new Cell(position: $position, species: new Species(type: 'A'));
		$grid = new Grid(size: 5, cells: [
			$position->key() => $cell,
		]);

		$found = $grid->getCellAt(position: $position);

		self::assertNotNull(actual: $found);
		self::assertSame(expected: 'A', actual: $found->species->type);
	}

	#[Test]
	public function getNeighbors(): void
	{
		$center = new Position(x: 1, y: 1);
		$neighbor = new Position(x: 0, y: 0);
		$farAway = new Position(x: 3, y: 3);

		$grid = new Grid(size: 5, cells: [
			$neighbor->key() => new Cell(position: $neighbor, species: new Species(type: 'A')),
			$farAway->key() => new Cell(position: $farAway, species: new Species(type: 'B')),
		]);

		$neighbors = $grid->getNeighbors(position: $center);

		self::assertCount(expectedCount: 1, haystack: $neighbors);
		self::assertSame(expected: 'A', actual: $neighbors[0]->species->type);
	}

	#[Test]
	public function getEmptyNeighborPositions(): void
	{
		$center = new Position(x: 1, y: 1);
		$occupied = new Position(x: 0, y: 0);

		$grid = new Grid(size: 5, cells: [
			$center->key() => new Cell(position: $center, species: new Species(type: 'A')),
			$occupied->key() => new Cell(position: $occupied, species: new Species(type: 'A')),
		]);

		$empty = $grid->getEmptyNeighborPositions(position: $center);

		// center has 8 neighbors, 1 is occupied → 7 empty
		self::assertCount(expectedCount: 7, haystack: $empty);
	}

	#[Test]
	public function getNeighborSpeciesCounts(): void
	{
		$center = new Position(x: 1, y: 1);

		$grid = new Grid(size: 5, cells: [
			'0:0' => new Cell(position: new Position(x: 0, y: 0), species: new Species(type: 'A')),
			'0:1' => new Cell(position: new Position(x: 0, y: 1), species: new Species(type: 'A')),
			'2:2' => new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'B')),
		]);

		$counts = $grid->getNeighborSpeciesCounts(position: $center);

		self::assertSame(expected: 2, actual: $counts['A']);
		self::assertSame(expected: 1, actual: $counts['B']);
	}

	#[Test]
	public function withCellIsImmutable(): void
	{
		$grid = new Grid(size: 5);
		$cell = new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'A'));

		$newGrid = $grid->withCell(cell: $cell);

		self::assertNotSame(expected: $grid, actual: $newGrid);
		self::assertFalse(condition: $grid->hasCellAt(position: $cell->position));
		self::assertTrue(condition: $newGrid->hasCellAt(position: $cell->position));
	}

	#[Test]
	public function withoutCellAtIsImmutable(): void
	{
		$position = new Position(x: 1, y: 1);
		$cell = new Cell(position: $position, species: new Species(type: 'A'));
		$grid = new Grid(size: 5, cells: [
			$position->key() => $cell,
		]);

		$newGrid = $grid->withoutCellAt(position: $position);

		self::assertNotSame(expected: $grid, actual: $newGrid);
		self::assertTrue(condition: $grid->hasCellAt(position: $position));
		self::assertFalse(condition: $newGrid->hasCellAt(position: $position));
	}
}
