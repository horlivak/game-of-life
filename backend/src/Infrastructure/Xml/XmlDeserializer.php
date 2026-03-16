<?php

declare(strict_types=1);

namespace App\Infrastructure\Xml;

use App\Engine\Cell;
use App\Engine\Position;
use App\Engine\Species;
use App\Infrastructure\Xml\Exception\XmlDeserializationException;
use App\Simulation\SimulationInput;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;

final readonly class XmlDeserializer
{
	/**
	 * @throws XmlDeserializationException
	 * @throws InvalidArgumentException
	 */
	public function deserialize(string $xml): SimulationInput
	{
		if (trim(string: $xml) === '') {
			throw XmlDeserializationException::parseFailed();
		}

		$dom = new DOMDocument();

		$previousUseErrors = libxml_use_internal_errors(use_errors: true);

		$loaded = $dom->loadXML(source: $xml, options: LIBXML_NONET);

		libxml_clear_errors();
		libxml_use_internal_errors(use_errors: $previousUseErrors);

		if (!$loaded) {
			throw XmlDeserializationException::parseFailed();
		}

		$xpath = new DOMXPath(document: $dom);

		$dimension = $this->extractInt(xpath: $xpath, expression: '/life/world/dimension');
		$speciesCount = $this->extractInt(xpath: $xpath, expression: '/life/world/speciesCount');
		$iterationsCount = $this->extractInt(xpath: $xpath, expression: '/life/world/iterationsCount');

		$organismNodes = $xpath->query(expression: '/life/organisms/organism');

		if ($organismNodes === false) {
			throw XmlDeserializationException::parseFailed();
		}

		$cells = [];
		$speciesTypesMap = [];

		/** @var DOMElement $node */
		foreach ($organismNodes as $node) {
			$x = $this->extractIntFromElement(xpath: $xpath, context: $node, element: 'x_pos');
			$y = $this->extractIntFromElement(xpath: $xpath, context: $node, element: 'y_pos');
			$speciesType = $this->extractStringFromElement(xpath: $xpath, context: $node, element: 'species');

			$cells[] = new Cell(position: new Position(x: $x, y: $y), species: new Species(type: $speciesType));
			$speciesTypesMap[$speciesType] = true;
		}

		$actualSpeciesCount = count(value: $speciesTypesMap);
		if ($actualSpeciesCount !== $speciesCount) {
			throw XmlDeserializationException::speciesCountMismatch(expected: $speciesCount, actual: $actualSpeciesCount);
		}

		return new SimulationInput(
			dimension: $dimension,
			cells: $cells,
			iterations: $iterationsCount,
			speciesTypes: array_keys(array: $speciesTypesMap),
		);
	}

	/**
	 * @throws XmlDeserializationException
	 */
	private function extractInt(DOMXPath $xpath, string $expression): int
	{
		$nodes = $xpath->query(expression: $expression);

		$lastSlash = strrpos(haystack: $expression, needle: '/');
		$element = $lastSlash !== false
			? substr(string: $expression, offset: $lastSlash + 1)
			: $expression;

		if ($nodes === false || $nodes->length === 0) {
			throw XmlDeserializationException::missingElement(element: $element);
		}

		/** @var DOMElement $node */
		$node = $nodes->item(index: 0);

		return $this->parseIntValue(value: $node->textContent, element: $element);
	}

	/**
	 * @throws XmlDeserializationException
	 */
	private function extractIntFromElement(DOMXPath $xpath, DOMElement $context, string $element): int
	{
		$nodes = $xpath->query(expression: $element, contextNode: $context);
		if ($nodes === false || $nodes->length === 0) {
			throw XmlDeserializationException::missingElement(element: $element);
		}

		/** @var DOMElement $node */
		$node = $nodes->item(index: 0);

		return $this->parseIntValue(value: $node->textContent, element: $element);
	}

	/**
	 * @throws XmlDeserializationException
	 */
	private function extractStringFromElement(DOMXPath $xpath, DOMElement $context, string $element): string
	{
		$nodes = $xpath->query(expression: $element, contextNode: $context);
		if ($nodes === false || $nodes->length === 0) {
			throw XmlDeserializationException::missingElement(element: $element);
		}

		/** @var DOMElement $node */
		$node = $nodes->item(index: 0);

		return $node->textContent;
	}

	/**
	 * @throws XmlDeserializationException
	 */
	private function parseIntValue(string $value, string $element): int
	{
		$trimmed = trim(string: $value);
		$filtered = filter_var(value: $trimmed, filter: FILTER_VALIDATE_INT);

		if ($filtered === false) {
			throw XmlDeserializationException::invalidIntValue(element: $element, value: $trimmed);
		}

		return $filtered;
	}
}
