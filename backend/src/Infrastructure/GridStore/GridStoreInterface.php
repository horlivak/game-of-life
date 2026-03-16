<?php

declare(strict_types=1);

namespace App\Infrastructure\GridStore;

use App\Engine\Grid;
use App\Infrastructure\GridStore\Exception\GridStoreException;

interface GridStoreInterface
{
	/**
	 * @throws GridStoreException
	 */
	public function save(int $simulationId, Grid $grid): void;

	/**
	 * @throws GridStoreException
	 */
	public function load(int $simulationId): ?Grid;

	public function delete(int $simulationId): void;
}
