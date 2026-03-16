<?php

declare(strict_types=1);

namespace App\Engine;

use App\Engine\Rule\BirthRuleInterface;
use App\Engine\Rule\DeathRuleInterface;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class SimulationEngine
{
	public const int MAX_ITERATIONS = 4_000_000;

	/**
	 * @param iterable<DeathRuleInterface> $deathRules
	 * @param iterable<BirthRuleInterface> $birthRules
	 */
	public function __construct(
		#[AutowireIterator(tag: 'app.death_rule')]
		private iterable $deathRules,
		#[AutowireIterator(tag: 'app.birth_rule')]
		private iterable $birthRules,
		private ConflictResolverInterface $conflictResolver,
	) {
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function tick(Grid $grid): Grid
	{
		$survivors = $this->applySurvivalRules(grid: $grid);

		$birthArrays = [];
		foreach ($this->birthRules as $birthRule) {
			$birthArrays[] = $birthRule->findBirths(grid: $grid);
		}

		$allCells = array_merge($survivors, ...$birthArrays);

		return $this->buildNewGrid(size: $grid->size, cells: $allCells);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function simulate(Grid $grid, int $iterations): Grid
	{
		if ($iterations < 0 || $iterations > self::MAX_ITERATIONS) {
			throw new InvalidArgumentException(
				message: sprintf('Iterations must be between 0 and %d, got %d.', self::MAX_ITERATIONS, $iterations),
			);
		}

		for ($i = 0; $i < $iterations; $i++) {
			if ($grid->cells === []) {
				break;
			}

			$grid = $this->tick(grid: $grid);
		}

		return $grid;
	}

	/**
	 * @return list<Cell>
	 * @throws InvalidArgumentException
	 */
	private function applySurvivalRules(Grid $grid): array
	{
		$survivors = [];

		foreach ($grid->cells as $cell) {
			$shouldDie = false;

			foreach ($this->deathRules as $deathRule) {
				if ($deathRule->shouldDie(cell: $cell, grid: $grid)) {
					$shouldDie = true;

					break;
				}
			}

			if (!$shouldDie) {
				$survivors[] = $cell;
			}
		}

		return $survivors;
	}

	/**
	 * @param list<Cell> $cells
	 * @throws InvalidArgumentException
	 */
	private function buildNewGrid(int $size, array $cells): Grid
	{
		$resolved = $this->conflictResolver->resolve(cells: $cells);

		$indexed = [];
		foreach ($resolved as $cell) {
			$indexed[$cell->position->key()] = $cell;
		}

		return Grid::createTrusted(size: $size, cells: $indexed);
	}
}
