<?php

declare(strict_types=1);

namespace App\Tests\Unit\Engine;

use App\Engine\Cell;
use App\Engine\ConflictResolver;
use App\Engine\Position;
use App\Engine\Species;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConflictResolverTest extends TestCase
{
	private ConflictResolver $resolver;

	protected function setUp(): void
	{
		$this->resolver = new ConflictResolver();
	}

	#[Test]
	public function noConflicts(): void
	{
		$cells = [
			new Cell(position: new Position(x: 0, y: 0), species: new Species(type: 'A')),
			new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'B')),
		];

		$resolved = $this->resolver->resolve(cells: $cells);

		self::assertCount(expectedCount: 2, haystack: $resolved);
	}

	#[Test]
	public function emptyInput(): void
	{
		$resolved = $this->resolver->resolve(cells: []);

		self::assertCount(expectedCount: 0, haystack: $resolved);
	}

	#[Test]
	public function singleConflict(): void
	{
		$position = new Position(x: 1, y: 1);
		$cells = [
			new Cell(position: $position, species: new Species(type: 'A')),
			new Cell(position: $position, species: new Species(type: 'B')),
		];

		$resolved = $this->resolver->resolve(cells: $cells);

		self::assertCount(expectedCount: 1, haystack: $resolved);
		self::assertContains(needle: $resolved[0]->species->type, haystack: ['A', 'B']);
	}

	#[Test]
	public function singleConflictIsNonDeterministic(): void
	{
		$position = new Position(x: 1, y: 1);
		$cells = [
			new Cell(position: $position, species: new Species(type: 'A')),
			new Cell(position: $position, species: new Species(type: 'B')),
		];

		$results = [];
		for ($i = 0; $i < 100; $i++) {
			$resolved = $this->resolver->resolve(cells: $cells);
			$results[$resolved[0]->species->type] = true;
		}

		self::assertArrayHasKey(
			key: 'A',
			array: $results,
			message: 'Expected species A to win at least once in 100 iterations.'
		);
		self::assertArrayHasKey(
			key: 'B',
			array: $results,
			message: 'Expected species B to win at least once in 100 iterations.'
		);
	}

	#[Test]
	public function multipleConflicts(): void
	{
		$pos1 = new Position(x: 0, y: 0);
		$pos2 = new Position(x: 1, y: 1);
		$cells = [
			new Cell(position: $pos1, species: new Species(type: 'A')),
			new Cell(position: $pos1, species: new Species(type: 'B')),
			new Cell(position: $pos2, species: new Species(type: 'C')),
			new Cell(position: $pos2, species: new Species(type: 'D')),
		];

		$resolved = $this->resolver->resolve(cells: $cells);

		self::assertCount(expectedCount: 2, haystack: $resolved);

		$keys = array_map(callback: static fn (Cell $cell): string => $cell->position->key(), array: $resolved);
		self::assertContains(needle: '0:0', haystack: $keys);
		self::assertContains(needle: '1:1', haystack: $keys);
	}
}
