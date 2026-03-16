<?php

declare(strict_types=1);

namespace App\Engine;

interface ConflictResolverInterface
{
	/**
	 * @param list<Cell> $cells
	 * @return list<Cell>
	 */
	public function resolve(array $cells): array;
}
