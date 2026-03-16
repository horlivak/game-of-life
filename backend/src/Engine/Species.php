<?php

declare(strict_types=1);

namespace App\Engine;

use InvalidArgumentException;

final readonly class Species
{
	/**
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		public string $type,
	) {
		if ($type === '') {
			throw new InvalidArgumentException(message: 'Species type must not be empty.');
		}
	}
}
