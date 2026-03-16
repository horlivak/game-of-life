<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Infrastructure\Serializer\Exception\UnresolvableParameterException;

final class MappingUtils
{
	/**
	 * @param mixed[] $data
	 * @throws UnresolvableParameterException
	 */
	public static function extractString(array $data, string $key): string
	{
		self::assertKeyExists(data: $data, key: $key);

		if (!is_string(value: $data[$key])) {
			throw UnresolvableParameterException::invalidType(
				key: $key,
				expectedType: 'string',
				actualType: get_debug_type(value: $data[$key])
			);
		}

		return $data[$key];
	}

	/**
	 * @param mixed[] $data
	 * @throws UnresolvableParameterException
	 */
	public static function extractInt(array $data, string $key): int
	{
		self::assertKeyExists(data: $data, key: $key);

		if (!is_int(value: $data[$key])) {
			throw UnresolvableParameterException::invalidType(
				key: $key,
				expectedType: 'int',
				actualType: get_debug_type(value: $data[$key])
			);
		}

		return $data[$key];
	}

	/**
	 * @param mixed[] $data
	 * @return list<mixed>
	 * @throws UnresolvableParameterException
	 */
	public static function extractArray(array $data, string $key): array
	{
		self::assertKeyExists(data: $data, key: $key);

		if (!is_array(value: $data[$key])) {
			throw UnresolvableParameterException::invalidType(
				key: $key,
				expectedType: 'array',
				actualType: get_debug_type(value: $data[$key])
			);
		}

		return array_values(array: $data[$key]);
	}

	/**
	 * @param mixed[] $data
	 * @throws UnresolvableParameterException
	 */
	private static function assertKeyExists(array $data, string $key): void
	{
		if (!array_key_exists(key: $key, array: $data)) {
			throw UnresolvableParameterException::missing(key: $key);
		}
	}
}
