<?php

declare(strict_types=1);

namespace App\Engine\Rule;

use App\Engine\Cell;
use App\Engine\Grid;
use InvalidArgumentException;

interface DeathRuleInterface
{
	/**
	 * @throws InvalidArgumentException
	 */
	public function shouldDie(Cell $cell, Grid $grid): bool;
}
