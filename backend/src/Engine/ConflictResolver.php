<?php

declare(strict_types=1);

namespace App\Engine;

final readonly class ConflictResolver implements ConflictResolverInterface
{
	/**
	 * @param list<Cell> $cells
	 * @return list<Cell>
	 */
	public function resolve(array $cells): array
	{
		/** @var array<string, list<Cell>> $byPosition */
		$byPosition = [];

		foreach ($cells as $cell) {
			$byPosition[$cell->position->key()][] = $cell;
		}

		$resolved = [];

		foreach ($byPosition as $candidates) {
			if (count(value: $candidates) === 1) {
				$resolved[] = $candidates[0];

				continue;
			}

			/** @var non-empty-list<Cell> $candidates */
			$resolved[] = $candidates[array_rand(array: $candidates)];
		}

		return $resolved;
	}
}
