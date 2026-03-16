<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer\Exception;

use RuntimeException;

final class UnresolvableParameterException extends RuntimeException
{
	public static function missing(string $key): self
	{
		return new self(message: sprintf('Missing required parameter "%s".', $key));
	}

	public static function invalidType(string $key, string $expectedType, string $actualType): self
	{
		return new self(message: sprintf('Parameter "%s" expected %s, got %s.', $key, $expectedType, $actualType));
	}
}
