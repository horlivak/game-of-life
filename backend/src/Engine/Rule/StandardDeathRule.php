<?php

declare(strict_types=1);

namespace App\Engine\Rule;

use App\Engine\Cell;
use App\Engine\Grid;

/**
 * Standard Conway's death rule: an organism dies if it has fewer than 2
 * or more than 3 same-species neighbors (underpopulation / overcrowding).
 */
final readonly class StandardDeathRule implements DeathRuleInterface
{
	public function shouldDie(Cell $cell, Grid $grid): bool
	{
		$sameSpeciesCount = $grid->countSameSpeciesNeighbors(
			position: $cell->position,
			speciesType: $cell->species->type,
		);

		return $sameSpeciesCount < 2 || $sameSpeciesCount > 3;
	}
}
