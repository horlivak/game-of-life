<?php

declare(strict_types=1);

namespace App\Tests\Unit\Engine\Rule;

use App\Engine\Cell;
use App\Engine\Grid;
use App\Engine\Position;
use App\Engine\Rule\ReproductionRule;
use App\Engine\Species;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReproductionRuleTest extends TestCase
{
	private ReproductionRule $rule;

	protected function setUp(): void
	{
		$this->rule = new ReproductionRule();
	}

	#[Test]
	public function birthWithThreeSameSpecies(): void
	{
		// Three 'A' cells around empty position (1,1)
		$grid = new Grid(size: 5, cells: [
			'0:0' => new Cell(position: new Position(x: 0, y: 0), species: new Species(type: 'A')),
			'0:1' => new Cell(position: new Position(x: 0, y: 1), species: new Species(type: 'A')),
			'0:2' => new Cell(position: new Position(x: 0, y: 2), species: new Species(type: 'A')),
		]);

		$births = $this->rule->findBirths(grid: $grid);

		// Position (1,1) should get a new 'A' cell
		$birthPositionKeys = array_map(
			callback: static fn (Cell $cell): string => $cell->position->key(),
			array: $births,
		);
		self::assertContains(needle: '1:1', haystack: $birthPositionKeys);

		$birthAt11 = array_filter(
			array: $births,
			callback: static fn (Cell $cell): bool => $cell->position->key() === '1:1',
		);
		$birthCell = array_values(array: $birthAt11)[0];
		self::assertSame(expected: 'A', actual: $birthCell->species->type);
	}

	#[Test]
	public function noBirthWithTwoNeighbors(): void
	{
		// Only 2 neighbors at empty position — no birth
		$grid = new Grid(size: 5, cells: [
			'0:0' => new Cell(position: new Position(x: 0, y: 0), species: new Species(type: 'A')),
			'0:1' => new Cell(position: new Position(x: 0, y: 1), species: new Species(type: 'A')),
		]);

		$births = $this->rule->findBirths(grid: $grid);

		// No position has exactly 3 same-species neighbors
		$birthPositionKeys = array_map(
			callback: static fn (Cell $cell): string => $cell->position->key(),
			array: $births,
		);
		self::assertNotContains(needle: '1:0', haystack: $birthPositionKeys);
		self::assertNotContains(needle: '1:1', haystack: $birthPositionKeys);
	}

	#[Test]
	public function noBirthOnOccupied(): void
	{
		// Position (1,1) is occupied — reproduction won't happen there
		$grid = new Grid(size: 5, cells: [
			'0:0' => new Cell(position: new Position(x: 0, y: 0), species: new Species(type: 'A')),
			'0:1' => new Cell(position: new Position(x: 0, y: 1), species: new Species(type: 'A')),
			'0:2' => new Cell(position: new Position(x: 0, y: 2), species: new Species(type: 'A')),
			'1:1' => new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'B')),
		]);

		$births = $this->rule->findBirths(grid: $grid);

		$birthPositionKeys = array_map(
			callback: static fn (Cell $cell): string => $cell->position->key(),
			array: $births,
		);
		self::assertNotContains(needle: '1:1', haystack: $birthPositionKeys);
	}

	#[Test]
	public function noBirthOnEmptyGrid(): void
	{
		$grid = new Grid(size: 5);

		$births = $this->rule->findBirths(grid: $grid);

		self::assertCount(expectedCount: 0, haystack: $births);
	}

	#[Test]
	public function multiSpeciesConflict(): void
	{
		// Position (2,2) has 3 'A' neighbors and 3 'B' neighbors
		$grid = new Grid(size: 5, cells: [
			'1:1' => new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'A')),
			'1:2' => new Cell(position: new Position(x: 1, y: 2), species: new Species(type: 'A')),
			'1:3' => new Cell(position: new Position(x: 1, y: 3), species: new Species(type: 'A')),
			'3:1' => new Cell(position: new Position(x: 3, y: 1), species: new Species(type: 'B')),
			'3:2' => new Cell(position: new Position(x: 3, y: 2), species: new Species(type: 'B')),
			'3:3' => new Cell(position: new Position(x: 3, y: 3), species: new Species(type: 'B')),
		]);

		$births = $this->rule->findBirths(grid: $grid);

		// Position (2,2) should have a birth — either A or B
		$birthAt22 = array_filter(
			array: $births,
			callback: static fn (Cell $cell): bool => $cell->position->key() === '2:2',
		);
		self::assertCount(expectedCount: 1, haystack: $birthAt22);

		$birthCell = array_values(array: $birthAt22)[0];
		self::assertContains(needle: $birthCell->species->type, haystack: ['A', 'B']);
	}

	#[Test]
	public function multiSpeciesConflictIsNonDeterministic(): void
	{
		$grid = new Grid(size: 5, cells: [
			'1:1' => new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'A')),
			'1:2' => new Cell(position: new Position(x: 1, y: 2), species: new Species(type: 'A')),
			'1:3' => new Cell(position: new Position(x: 1, y: 3), species: new Species(type: 'A')),
			'3:1' => new Cell(position: new Position(x: 3, y: 1), species: new Species(type: 'B')),
			'3:2' => new Cell(position: new Position(x: 3, y: 2), species: new Species(type: 'B')),
			'3:3' => new Cell(position: new Position(x: 3, y: 3), species: new Species(type: 'B')),
		]);

		$results = [];
		for ($i = 0; $i < 100; $i++) {
			$births = $this->rule->findBirths(grid: $grid);
			$birthAt22 = array_filter(
				array: $births,
				callback: static fn (Cell $cell): bool => $cell->position->key() === '2:2',
			);
			$birthCell = array_values(array: $birthAt22)[0];
			$results[$birthCell->species->type] = true;
		}

		self::assertArrayHasKey(
			key: 'A',
			array: $results,
			message: 'Expected species A to be born at least once in 100 iterations.'
		);
		self::assertArrayHasKey(
			key: 'B',
			array: $results,
			message: 'Expected species B to be born at least once in 100 iterations.'
		);
	}
}
