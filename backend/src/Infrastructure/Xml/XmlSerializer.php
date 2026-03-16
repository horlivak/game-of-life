<?php

declare(strict_types=1);

namespace App\Infrastructure\Xml;

use App\Infrastructure\Xml\Exception\XmlSerializationException;
use App\Simulation\SimulationResult;
use DOMDocument;
use DOMElement;
use DOMException;

final readonly class XmlSerializer
{
	/**
	 * @throws XmlSerializationException
	 */
	public function render(SimulationResult $result): string
	{
		try {
			$dom = new DOMDocument(version: '1.0', encoding: 'UTF-8');
			$dom->formatOutput = true;

			$life = $dom->createElement(localName: 'life');
			$dom->appendChild(node: $life);

			$world = $dom->createElement(localName: 'world');
			$life->appendChild(node: $world);

			$this->appendTextElement(dom: $dom, parent: $world, name: 'dimension', value: (string) $result->grid->size);
			$this->appendTextElement(
				dom: $dom,
				parent: $world,
				name: 'speciesCount',
				value: (string) count(value: $result->speciesTypes)
			);
			$this->appendTextElement(
				dom: $dom,
				parent: $world,
				name: 'iterationsCount',
				value: (string) $result->iterationsCount,
			);

			$organisms = $dom->createElement(localName: 'organisms');
			$life->appendChild(node: $organisms);

			foreach ($result->grid->cells as $cell) {
				$element = $dom->createElement(localName: 'organism');
				$organisms->appendChild(node: $element);

				$this->appendTextElement(dom: $dom, parent: $element, name: 'x_pos', value: (string) $cell->position->x);
				$this->appendTextElement(dom: $dom, parent: $element, name: 'y_pos', value: (string) $cell->position->y);
				$this->appendTextElement(dom: $dom, parent: $element, name: 'species', value: $cell->species->type);
			}

			$xml = $dom->saveXML();
			if ($xml === false) {
				throw XmlSerializationException::outputFailed();
			}

			return $xml;
		} catch (DOMException $domException) {
			throw XmlSerializationException::outputFailed(previous: $domException);
		}
	}

	/**
	 * @throws DOMException
	 */
	private function appendTextElement(DOMDocument $dom, DOMElement $parent, string $name, string $value): void
	{
		$element = $dom->createElement(localName: $name);
		$element->textContent = $value;

		$parent->appendChild(node: $element);
	}
}
