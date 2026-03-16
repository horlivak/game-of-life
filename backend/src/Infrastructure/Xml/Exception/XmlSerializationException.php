<?php

declare(strict_types=1);

namespace App\Infrastructure\Xml\Exception;

use RuntimeException;
use Throwable;

final class XmlSerializationException extends RuntimeException
{
	public static function outputFailed(?Throwable $previous = null): self
	{
		return new self(message: 'Failed to generate XML output', previous: $previous);
	}
}
