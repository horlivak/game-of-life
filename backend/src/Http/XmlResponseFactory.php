<?php

declare(strict_types=1);

namespace App\Http;

use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final readonly class XmlResponseFactory
{
	/**
	 * @throws DOMException
	 * @throws InvalidArgumentException
	 */
	public function error(string $message, int $statusCode): Response
	{
		$dom = $this->createDocument();
		$error = $dom->createElement(localName: 'error');
		$dom->appendChild(node: $error);

		$this->appendTextElement(dom: $dom, parent: $error, name: 'message', value: $message);

		return $this->createResponse(dom: $dom, statusCode: $statusCode);
	}

	/**
	 * @param list<string> $errors
	 *
	 * @throws DOMException
	 * @throws InvalidArgumentException
	 */
	public function errors(array $errors, int $statusCode): Response
	{
		$dom = $this->createDocument();
		$errorsEl = $dom->createElement(localName: 'errors');
		$dom->appendChild(node: $errorsEl);

		foreach ($errors as $error) {
			$this->appendTextElement(dom: $dom, parent: $errorsEl, name: 'error', value: $error);
		}

		return $this->createResponse(dom: $dom, statusCode: $statusCode);
	}

	/**
	 * @throws DOMException
	 * @throws InvalidArgumentException
	 */
	public function simulationCreated(int $id, string $status): Response
	{
		$dom = $this->createDocument();
		$simulation = $dom->createElement(localName: 'simulation');
		$dom->appendChild(node: $simulation);

		$this->appendTextElement(dom: $dom, parent: $simulation, name: 'id', value: (string) $id);
		$this->appendTextElement(dom: $dom, parent: $simulation, name: 'status', value: $status);

		return $this->createResponse(dom: $dom, statusCode: Response::HTTP_ACCEPTED);
	}

	/**
	 * @throws DOMException
	 * @throws InvalidArgumentException
	 */
	public function simulationStatus(
		int $id,
		string $status,
		int $dimension,
		int $speciesCount,
		int $iterationsCount,
		DateTimeImmutable $createdAt,
		?DateTimeImmutable $completedAt,
	): Response {
		$dom = $this->createDocument();
		$element = $dom->createElement(localName: 'simulation');
		$dom->appendChild(node: $element);

		$this->appendTextElement(dom: $dom, parent: $element, name: 'id', value: (string) $id);
		$this->appendTextElement(dom: $dom, parent: $element, name: 'status', value: $status);
		$this->appendTextElement(dom: $dom, parent: $element, name: 'dimension', value: (string) $dimension);
		$this->appendTextElement(dom: $dom, parent: $element, name: 'speciesCount', value: (string) $speciesCount);
		$this->appendTextElement(dom: $dom, parent: $element, name: 'iterationsCount', value: (string) $iterationsCount);
		$this->appendTextElement(dom: $dom, parent: $element, name: 'createdAt', value: $createdAt->format(format: 'c'));

		if ($completedAt !== null) {
			$this->appendTextElement(dom: $dom, parent: $element, name: 'completedAt', value: $completedAt->format(format: 'c'));
		}

		return $this->createResponse(dom: $dom, statusCode: Response::HTTP_OK);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function xml(string $xmlContent, int $statusCode = Response::HTTP_OK): Response
	{
		return new Response(content: $xmlContent, status: $statusCode, headers: [
			'Content-Type' => 'application/xml',
		]);
	}

	private function createDocument(): DOMDocument
	{
		$dom = new DOMDocument(version: '1.0', encoding: 'UTF-8');
		$dom->formatOutput = true;

		return $dom;
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

	/**
	 * @throws InvalidArgumentException
	 */
	private function createResponse(DOMDocument $dom, int $statusCode): Response
	{
		$xml = $dom->saveXML();
		if ($xml === false) {
			$xml = '';
		}

		return new Response(content: $xml, status: $statusCode, headers: [
			'Content-Type' => 'application/xml',
		]);
	}
}
