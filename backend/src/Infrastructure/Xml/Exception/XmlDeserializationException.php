<?php

declare(strict_types=1);

namespace App\Infrastructure\Xml\Exception;

use RuntimeException;

final class XmlDeserializationException extends RuntimeException
{
	public static function parseFailed(): self
	{
		return new self(message: 'Failed to parse XML input');
	}

	public static function missingElement(string $element): self
	{
		return new self(message: sprintf('Missing required element: <%s>', $element));
	}

	public static function invalidIntValue(string $element, string $value): self
	{
		return new self(message: sprintf('Element <%s> must contain a valid integer, got "%s".', $element, $value));
	}

	public static function speciesCountMismatch(int $expected, int $actual): self
	{
		return new self(message: sprintf(
			'Species count mismatch: <speciesCount> declares %d species, but %d found in organisms.',
			$expected,
			$actual,
		));
	}
}
