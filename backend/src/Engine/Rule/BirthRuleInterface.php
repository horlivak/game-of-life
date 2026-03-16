<?php

declare(strict_types=1);

namespace App\Engine\Rule;

use App\Engine\Cell;
use App\Engine\Grid;
use InvalidArgumentException;

interface BirthRuleInterface
{
	/**
	 * @return list<Cell>
	 * @throws InvalidArgumentException
	 */
	public function findBirths(Grid $grid): array;
}
