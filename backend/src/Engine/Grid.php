<?php

declare(strict_types=1);

namespace App\Engine;

use InvalidArgumentException;

final class Grid
{
	private const array OFFSETS = [[-1, -1], [-1, 0], [-1, 1], [0, -1], [0, 1], [1, -1], [1, 0], [1, 1]];

	private static bool $trusted = false;

	/**
	 * @param array<string, Cell> $cells Indexed by Position::key()
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		public readonly int $size,
		public readonly array $cells = [],
	) {
		if (self::$trusted) {
			return;
		}

		if ($size < 1) {
			throw new InvalidArgumentException(message: 'Grid size must be at least 1.');
		}

		foreach ($cells as $key => $cell) {
			$pos = $cell->position;

			if ($key !== $pos->key()) {
				throw new InvalidArgumentException(
					message: sprintf('Cell key "%s" does not match position key "%s".', $key, $pos->key()),
				);
			}

			if ($pos->x < 0 || $pos->x >= $size || $pos->y < 0 || $pos->y >= $size) {
				throw new InvalidArgumentException(
					message: sprintf('Cell at position %s is out of grid bounds (size %d).', $pos->key(), $size),
				);
			}
		}
	}

	/**
	 * @param array<string, Cell> $cells Pre-validated cells indexed by Position::key()
	 * @throws InvalidArgumentException
	 */
	public static function createTrusted(int $size, array $cells): self
	{
		self::$trusted = true;

		try {
			return new self(size: $size, cells: $cells);
		} finally {
			self::$trusted = false;
		}
	}

	/**
	 * @param list<Cell> $cells
	 * @throws InvalidArgumentException
	 */
	public static function fromCells(int $size, array $cells): self
	{
		$indexed = [];

		foreach ($cells as $cell) {
			$indexed[$cell->position->key()] = $cell;
		}

		return new self(size: $size, cells: $indexed);
	}

	public function getCellAt(Position $position): ?Cell
	{
		$key = $position->key();

		if (!array_key_exists(key: $key, array: $this->cells)) {
			return null;
		}

		return $this->cells[$key];
	}

	public function getCellAtCoords(int $x, int $y): ?Cell
	{
		$key = $x . ':' . $y;

		return $this->cells[$key] ?? null;
	}

	public function hasCellAt(Position $position): bool
	{
		return array_key_exists(key: $position->key(), array: $this->cells);
	}

	/**
	 * @return list<Position>
	 * @throws InvalidArgumentException
	 */
	public function getNeighborPositions(Position $position): array
	{
		$neighbors = [];

		foreach (self::OFFSETS as [$dx, $dy]) {
			$nx = $position->x + $dx;
			$ny = $position->y + $dy;

			if ($this->isInBounds(x: $nx, y: $ny)) {
				$neighbors[] = new Position(x: $nx, y: $ny);
			}
		}

		return $neighbors;
	}

	/**
	 * @return list<Cell>
	 */
	public function getNeighbors(Position $position): array
	{
		$neighbors = [];

		foreach (self::OFFSETS as [$dx, $dy]) {
			$nx = $position->x + $dx;
			$ny = $position->y + $dy;

			if ($this->isInBounds(x: $nx, y: $ny)) {
				$key = $nx . ':' . $ny;

				if (array_key_exists(key: $key, array: $this->cells)) {
					$neighbors[] = $this->cells[$key];
				}
			}
		}

		return $neighbors;
	}

	/**
	 * @return list<Position>
	 * @throws InvalidArgumentException
	 */
	public function getEmptyNeighborPositions(Position $position): array
	{
		$empty = [];

		foreach (self::OFFSETS as [$dx, $dy]) {
			$nx = $position->x + $dx;
			$ny = $position->y + $dy;

			if ($this->isInBounds(x: $nx, y: $ny)) {
				$key = $nx . ':' . $ny;

				if (!array_key_exists(key: $key, array: $this->cells)) {
					$empty[] = new Position(x: $nx, y: $ny);
				}
			}
		}

		return $empty;
	}

	/**
	 * @return array<string, int> species type => count
	 */
	public function getNeighborSpeciesCounts(Position $position): array
	{
		$counts = [];

		foreach (self::OFFSETS as [$dx, $dy]) {
			$nx = $position->x + $dx;
			$ny = $position->y + $dy;

			if ($this->isInBounds(x: $nx, y: $ny)) {
				$key = $nx . ':' . $ny;

				if (array_key_exists(key: $key, array: $this->cells)) {
					$type = $this->cells[$key]->species->type;
					$counts[$type] = ($counts[$type] ?? 0) + 1;
				}
			}
		}

		return $counts;
	}

	public function countSameSpeciesNeighbors(Position $position, string $speciesType): int
	{
		$count = 0;

		foreach (self::OFFSETS as [$dx, $dy]) {
			$nx = $position->x + $dx;
			$ny = $position->y + $dy;

			if ($this->isInBounds(x: $nx, y: $ny)) {
				$key = $nx . ':' . $ny;

				if (array_key_exists(key: $key, array: $this->cells) && $this->cells[$key]->species->type === $speciesType) {
					$count++;
				}
			}
		}

		return $count;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function withCell(Cell $cell): self
	{
		$cells = $this->cells;
		$cells[$cell->position->key()] = $cell;

		return self::createTrusted(size: $this->size, cells: $cells);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function withoutCellAt(Position $position): self
	{
		$cells = $this->cells;
		unset($cells[$position->key()]);

		return self::createTrusted(size: $this->size, cells: $cells);
	}

	private function isInBounds(int $x, int $y): bool
	{
		return $x >= 0 && $x < $this->size && $y >= 0 && $y < $this->size;
	}
}
