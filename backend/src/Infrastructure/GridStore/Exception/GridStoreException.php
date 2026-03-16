<?php

declare(strict_types=1);

namespace App\Infrastructure\GridStore\Exception;

use RuntimeException;

final class GridStoreException extends RuntimeException
{
	public static function writeFailed(int $simulationId): self
	{
		return new self(message: sprintf('Failed to write grid for simulation #%d to Redis.', $simulationId));
	}

	public static function invalidData(int $simulationId): self
	{
		return new self(message: sprintf('Invalid grid data in Redis for simulation #%d.', $simulationId));
	}
}
