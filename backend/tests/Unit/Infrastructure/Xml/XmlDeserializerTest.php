<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Xml;

use App\Infrastructure\Xml\Exception\XmlDeserializationException;
use App\Infrastructure\Xml\XmlDeserializer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class XmlDeserializerTest extends TestCase
{
	private XmlDeserializer $deserializer;

	protected function setUp(): void
	{
		$this->deserializer = new XmlDeserializer();
	}

	#[Test]
	public function deserializesValidXml(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="UTF-8"?>
			<life>
			  <world>
			    <dimension>5</dimension>
			    <speciesCount>2</speciesCount>
			    <iterationsCount>3</iterationsCount>
			  </world>
			  <organisms>
			    <organism>
			      <x_pos>1</x_pos>
			      <y_pos>2</y_pos>
			      <species>A</species>
			    </organism>
			    <organism>
			      <x_pos>3</x_pos>
			      <y_pos>4</y_pos>
			      <species>B</species>
			    </organism>
			  </organisms>
			</life>
			XML;

		$input = $this->deserializer->deserialize(xml: $xml);

		self::assertSame(expected: 5, actual: $input->dimension);
		self::assertSame(expected: 3, actual: $input->iterations);
		self::assertCount(expectedCount: 2, haystack: $input->cells);
		self::assertEqualsCanonicalizing(['A', 'B'], $input->speciesTypes);

		self::assertSame(expected: 1, actual: $input->cells[0]->position->x);
		self::assertSame(expected: 2, actual: $input->cells[0]->position->y);
		self::assertSame(expected: 'A', actual: $input->cells[0]->species->type);

		self::assertSame(expected: 3, actual: $input->cells[1]->position->x);
		self::assertSame(expected: 4, actual: $input->cells[1]->position->y);
		self::assertSame(expected: 'B', actual: $input->cells[1]->species->type);
	}

	#[Test]
	public function throwsOnInvalidXml(): void
	{
		$this->expectException(XmlDeserializationException::class);
		$this->expectExceptionMessage('Failed to parse XML input');

		$this->deserializer->deserialize(xml: 'not valid xml <><>');
	}

	#[Test]
	public function throwsOnEmptyString(): void
	{
		$this->expectException(XmlDeserializationException::class);
		$this->expectExceptionMessage('Failed to parse XML input');

		$this->deserializer->deserialize(xml: '');
	}

	#[Test]
	public function throwsOnMissingDimension(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="UTF-8"?>
			<life>
			  <world>
			    <iterationsCount>3</iterationsCount>
			  </world>
			  <organisms/>
			</life>
			XML;

		$this->expectException(XmlDeserializationException::class);
		$this->expectExceptionMessage('Missing required element: <dimension>');

		$this->deserializer->deserialize(xml: $xml);
	}

	#[Test]
	public function throwsOnMissingSpeciesCount(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="UTF-8"?>
			<life>
			  <world>
			    <dimension>5</dimension>
			    <iterationsCount>3</iterationsCount>
			  </world>
			  <organisms/>
			</life>
			XML;

		$this->expectException(XmlDeserializationException::class);
		$this->expectExceptionMessage('Missing required element: <speciesCount>');

		$this->deserializer->deserialize(xml: $xml);
	}

	#[Test]
	public function throwsOnMissingIterationsCount(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="UTF-8"?>
			<life>
			  <world>
			    <dimension>5</dimension>
			    <speciesCount>0</speciesCount>
			  </world>
			  <organisms/>
			</life>
			XML;

		$this->expectException(XmlDeserializationException::class);
		$this->expectExceptionMessage('Missing required element: <iterationsCount>');

		$this->deserializer->deserialize(xml: $xml);
	}

	#[Test]
	public function throwsOnSpeciesCountMismatch(): void
	{
		$xml = <<<'XML'
			<?xml version="1.0" encoding="UTF-8"?>
			<life>
			  <world>
			    <dimension>5</dimension>
			    <speciesCount>3</speciesCount>
			    <iterationsCount>3</iterationsCount>
			  </world>
			  <organisms>
			    <organism>
			      <x_pos>1</x_pos>
			      <y_pos>2</y_pos>
			      <species>A</species>
			    </organism>
			  </organisms>
			</life>
			XML;

		$this->expectException(XmlDeserializationException::class);
		$this->expectExceptionMessage('Species count mismatch: <speciesCount> declares 3 species, but 1 found in organisms.');

		$this->deserializer->deserialize(xml: $xml);
	}
}
