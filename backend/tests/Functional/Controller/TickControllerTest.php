<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use DOMDocument;
use DOMNode;
use DOMXPath;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TickControllerTest extends WebTestCase
{
	private KernelBrowser $client;

	protected function setUp(): void
	{
		$this->client = self::createClient();
	}

	#[Test]
	public function tickSuccess(): void
	{
		$this->client->request(method: 'POST', uri: '/api/tick', content: $this->buildBlockXml());

		$response = $this->client->getResponse();
		self::assertSame(expected: 200, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		$organisms = $xpath->query(expression: '/life/organisms/organism');
		self::assertNotFalse(condition: $organisms);
		self::assertSame(expected: 4, actual: $organisms->length);
	}

	#[Test]
	public function tickSingleCellDies(): void
	{
		$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<life>
  <world>
    <dimension>5</dimension>
    <speciesCount>1</speciesCount>
    <iterationsCount>1</iterationsCount>
  </world>
  <organisms>
    <organism><x_pos>2</x_pos><y_pos>2</y_pos><species>A</species></organism>
  </organisms>
</life>
XML;

		$this->client->request(method: 'POST', uri: '/api/tick', content: $xml);

		$response = $this->client->getResponse();
		self::assertSame(expected: 200, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		$organisms = $xpath->query(expression: '/life/organisms/organism');
		self::assertNotFalse(condition: $organisms);
		self::assertSame(expected: 0, actual: $organisms->length);
	}

	#[Test]
	public function tickInvalidXml(): void
	{
		$this->client->request(method: 'POST', uri: '/api/tick', content: 'not-xml');

		$response = $this->client->getResponse();
		self::assertSame(expected: 400, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		self::assertSame(expected: 'Invalid XML', actual: $this->xpathValue(xpath: $xpath, expression: '/error/message'));
	}

	#[Test]
	public function tickEmptyBody(): void
	{
		$this->client->request(method: 'POST', uri: '/api/tick', content: '');

		$response = $this->client->getResponse();
		self::assertSame(expected: 400, actual: $response->getStatusCode());
	}

	private function buildBlockXml(): string
	{
		return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<life>
  <world>
    <dimension>10</dimension>
    <speciesCount>1</speciesCount>
    <iterationsCount>1</iterationsCount>
  </world>
  <organisms>
    <organism><x_pos>1</x_pos><y_pos>1</y_pos><species>A</species></organism>
    <organism><x_pos>1</x_pos><y_pos>2</y_pos><species>A</species></organism>
    <organism><x_pos>2</x_pos><y_pos>1</y_pos><species>A</species></organism>
    <organism><x_pos>2</x_pos><y_pos>2</y_pos><species>A</species></organism>
  </organisms>
</life>
XML;
	}

	private function parseXml(string $xml): DOMXPath
	{
		$dom = new DOMDocument();
		$dom->loadXML(source: $xml);

		return new DOMXPath(document: $dom);
	}

	private function xpathValue(DOMXPath $xpath, string $expression): ?string
	{
		$nodes = $xpath->query(expression: $expression);
		if ($nodes === false || $nodes->length === 0) {
			return null;
		}

		$node = $nodes->item(index: 0);
		if (!$node instanceof DOMNode) {
			return null;
		}

		return $node->textContent;
	}
}
