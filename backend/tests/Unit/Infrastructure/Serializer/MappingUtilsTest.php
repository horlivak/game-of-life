<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Serializer;

use App\Infrastructure\Serializer\Exception\UnresolvableParameterException;
use App\Infrastructure\Serializer\MappingUtils;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MappingUtilsTest extends TestCase
{
	#[Test]
	public function extractStringSuccess(): void
	{
		$result = MappingUtils::extractString(data: [
			'name' => 'foo',
		], key: 'name');

		self::assertSame(expected: 'foo', actual: $result);
	}

	#[Test]
	public function extractStringMissingKey(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('Missing required parameter "name"');

		MappingUtils::extractString(data: [], key: 'name');
	}

	#[Test]
	public function extractStringWrongType(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('expected string');

		MappingUtils::extractString(data: [
			'name' => 123,
		], key: 'name');
	}

	#[Test]
	public function extractIntSuccess(): void
	{
		$result = MappingUtils::extractInt(data: [
			'count' => 42,
		], key: 'count');

		self::assertSame(expected: 42, actual: $result);
	}

	#[Test]
	public function extractIntMissingKey(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('Missing required parameter "count"');

		MappingUtils::extractInt(data: [], key: 'count');
	}

	#[Test]
	public function extractIntWrongType(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('expected int');

		MappingUtils::extractInt(data: [
			'count' => 'not-int',
		], key: 'count');
	}

	#[Test]
	public function extractArraySuccess(): void
	{
		$result = MappingUtils::extractArray(data: [
			'items' => [1, 2, 3],
		], key: 'items');

		self::assertSame(expected: [1, 2, 3], actual: $result);
	}

	#[Test]
	public function extractArrayMissingKey(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('Missing required parameter "items"');

		MappingUtils::extractArray(data: [], key: 'items');
	}

	#[Test]
	public function extractArrayWrongType(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('expected array');

		MappingUtils::extractArray(data: [
			'items' => 'not-array',
		], key: 'items');
	}

	#[Test]
	public function extractArrayReturnsValues(): void
	{
		$result = MappingUtils::extractArray(data: [
			'items' => [
				'a' => 1,
				'b' => 2,
			],
		], key: 'items');

		self::assertSame(expected: [1, 2], actual: $result);
	}
}
