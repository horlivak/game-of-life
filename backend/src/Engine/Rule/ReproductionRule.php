<?php

declare(strict_types=1);

namespace App\Engine\Rule;

use App\Engine\Cell;
use App\Engine\Grid;
use App\Engine\Position;
use App\Engine\Species;
use InvalidArgumentException;

final readonly class ReproductionRule implements BirthRuleInterface
{
	/**
	 * @return list<Cell> New cells to be born
	 * @throws InvalidArgumentException
	 */
	public function findBirths(Grid $grid): array
	{
		$candidatesByPosition = $this->collectBirthCandidates(grid: $grid);

		$births = [];

		foreach ($candidatesByPosition as $candidate) {
			$births[] = new Cell(
				position: $candidate['position'],
				species: $this->pickSpecies(candidates: $candidate['species']),
			);
		}

		return $births;
	}

	/**
	 * @return list<array{position: Position, species: list<Species>}>
	 * @throws InvalidArgumentException
	 */
	private function collectBirthCandidates(Grid $grid): array
	{
		/** @var array<string, Position> $emptyNeighborPositions */
		$emptyNeighborPositions = [];

		foreach ($grid->cells as $cell) {
			foreach ($grid->getEmptyNeighborPositions(position: $cell->position) as $neighborPos) {
				$emptyNeighborPositions[$neighborPos->key()] = $neighborPos;
			}
		}

		$candidates = [];

		foreach ($emptyNeighborPositions as $position) {
			$species = $this->findCandidateSpeciesAt(grid: $grid, position: $position);

			if ($species !== []) {
				$candidates[] = [
					'position' => $position,
					'species' => $species,
				];
			}
		}

		return $candidates;
	}

	/**
	 * @return list<Species>
	 * @throws InvalidArgumentException
	 */
	private function findCandidateSpeciesAt(Grid $grid, Position $position): array
	{
		$candidates = [];

		foreach ($grid->getNeighborSpeciesCounts(position: $position) as $type => $count) {
			if ($count === 3) {
				$candidates[] = new Species(type: $type);
			}
		}

		return $candidates;
	}

	/**
	 * @param list<Species> $candidates
	 */
	private function pickSpecies(array $candidates): Species
	{
		if (count(value: $candidates) === 1) {
			return $candidates[0];
		}

		/** @var non-empty-list<Species> $candidates */
		return $candidates[array_rand(array: $candidates)];
	}
}
