<?php

declare(strict_types=1);

namespace App\Tests\Unit\Engine\Rule;

use App\Engine\Cell;
use App\Engine\Grid;
use App\Engine\Position;
use App\Engine\Rule\StandardDeathRule;
use App\Engine\Species;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StandardDeathRuleTest extends TestCase
{
	private StandardDeathRule $rule;

	protected function setUp(): void
	{
		$this->rule = new StandardDeathRule();
	}

	#[Test]
	public function diesWithZeroNeighbors(): void
	{
		$cell = new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'A'));
		$grid = new Grid(size: 5, cells: [
			$cell->position->key() => $cell,
		]);

		self::assertTrue(condition: $this->rule->shouldDie(cell: $cell, grid: $grid));
	}

	#[Test]
	public function diesWithOneNeighbor(): void
	{
		$cell = new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'A'));
		$neighbor = new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'A'));
		$grid = new Grid(size: 5, cells: [
			$cell->position->key() => $cell,
			$neighbor->position->key() => $neighbor,
		]);

		self::assertTrue(condition: $this->rule->shouldDie(cell: $cell, grid: $grid));
	}

	#[Test]
	public function survivesWithTwoNeighbors(): void
	{
		$cell = new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'A'));
		$n1 = new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'A'));
		$n2 = new Cell(position: new Position(x: 1, y: 2), species: new Species(type: 'A'));
		$grid = new Grid(size: 5, cells: [
			$cell->position->key() => $cell,
			$n1->position->key() => $n1,
			$n2->position->key() => $n2,
		]);

		self::assertFalse(condition: $this->rule->shouldDie(cell: $cell, grid: $grid));
	}

	#[Test]
	public function survivesWithThreeNeighbors(): void
	{
		$cell = new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'A'));
		$n1 = new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'A'));
		$n2 = new Cell(position: new Position(x: 1, y: 2), species: new Species(type: 'A'));
		$n3 = new Cell(position: new Position(x: 3, y: 3), species: new Species(type: 'A'));
		$grid = new Grid(size: 5, cells: [
			$cell->position->key() => $cell,
			$n1->position->key() => $n1,
			$n2->position->key() => $n2,
			$n3->position->key() => $n3,
		]);

		self::assertFalse(condition: $this->rule->shouldDie(cell: $cell, grid: $grid));
	}

	#[Test]
	public function diesWithFourNeighbors(): void
	{
		$cell = new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'A'));
		$n1 = new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'A'));
		$n2 = new Cell(position: new Position(x: 1, y: 2), species: new Species(type: 'A'));
		$n3 = new Cell(position: new Position(x: 3, y: 3), species: new Species(type: 'A'));
		$n4 = new Cell(position: new Position(x: 3, y: 2), species: new Species(type: 'A'));
		$grid = new Grid(size: 5, cells: [
			$cell->position->key() => $cell,
			$n1->position->key() => $n1,
			$n2->position->key() => $n2,
			$n3->position->key() => $n3,
			$n4->position->key() => $n4,
		]);

		self::assertTrue(condition: $this->rule->shouldDie(cell: $cell, grid: $grid));
	}

	#[Test]
	public function ignoresDifferentSpecies(): void
	{
		$cell = new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'A'));
		$sameSpecies = new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'A'));
		$differentSpecies = new Cell(position: new Position(x: 1, y: 2), species: new Species(type: 'B'));
		$grid = new Grid(size: 5, cells: [
			$cell->position->key() => $cell,
			$sameSpecies->position->key() => $sameSpecies,
			$differentSpecies->position->key() => $differentSpecies,
		]);

		// Only 1 same-species neighbor → should die (needs 2-3)
		self::assertTrue(condition: $this->rule->shouldDie(cell: $cell, grid: $grid));
	}
}
