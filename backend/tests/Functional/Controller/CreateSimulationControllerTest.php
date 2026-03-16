<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Simulation\SimulationService;
use App\Tests\Functional\DatabaseResetTrait;
use DOMDocument;
use DOMNode;
use DOMXPath;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateSimulationControllerTest extends WebTestCase
{
	use DatabaseResetTrait;

	private KernelBrowser $client;

	protected function setUp(): void
	{
		$this->client = self::createClient();
		$this->resetDatabase();
		$this->resetRedis();
	}

	#[Test]
	public function createSuccess(): void
	{
		$this->client->request(method: 'POST', uri: '/api/simulations', content: $this->buildXmlPayload());

		$response = $this->client->getResponse();
		self::assertSame(expected: 202, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		self::assertNotNull(actual: $this->xpathValue(xpath: $xpath, expression: '/simulation/id'));
		self::assertNotNull(actual: $this->xpathValue(xpath: $xpath, expression: '/simulation/status'));
	}

	#[Test]
	public function createAndRunSynchronously(): void
	{
		$this->client->request(method: 'POST', uri: '/api/simulations', content: $this->buildXmlPayload());

		$response = $this->client->getResponse();
		self::assertSame(expected: 202, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		$simulationId = (int) $this->xpathValue(xpath: $xpath, expression: '/simulation/id');

		/** @var SimulationService $service */
		$service = self::getContainer()->get(id: SimulationService::class);
		$service->run(simulationId: $simulationId);

		$this->client->request(method: 'GET', uri: '/api/simulations/' . $simulationId . '/status');

		$statusResponse = $this->client->getResponse();
		$statusXpath = $this->parseXml(xml: (string) $statusResponse->getContent());
		self::assertSame(
			expected: 'completed',
			actual: $this->xpathValue(xpath: $statusXpath, expression: '/simulation/status')
		);
	}

	#[Test]
	public function createInvalidXml(): void
	{
		$this->client->request(method: 'POST', uri: '/api/simulations', content: 'not-xml');

		$response = $this->client->getResponse();
		self::assertSame(expected: 400, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		self::assertSame(expected: 'Invalid XML', actual: $this->xpathValue(xpath: $xpath, expression: '/error/message'));
	}

	#[Test]
	public function createEmptyBody(): void
	{
		$this->client->request(method: 'POST', uri: '/api/simulations', content: '');

		$response = $this->client->getResponse();
		self::assertSame(expected: 400, actual: $response->getStatusCode());
	}

	#[Test]
	public function createDimensionZero(): void
	{
		$xml = $this->buildXmlPayloadWithOverrides(dimension: 0);

		$this->client->request(method: 'POST', uri: '/api/simulations', content: $xml);

		$response = $this->client->getResponse();
		self::assertSame(expected: 400, actual: $response->getStatusCode());
	}

	#[Test]
	public function createNegativeIterations(): void
	{
		$xml = $this->buildXmlPayloadWithOverrides(iterationsCount: -1);

		$this->client->request(method: 'POST', uri: '/api/simulations', content: $xml);

		$response = $this->client->getResponse();
		self::assertSame(expected: 400, actual: $response->getStatusCode());
	}

	#[Test]
	public function createEmptyOrganisms(): void
	{
		$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<life>
  <world>
    <dimension>10</dimension>
    <speciesCount>2</speciesCount>
    <iterationsCount>5</iterationsCount>
  </world>
  <organisms/>
</life>
XML;

		$this->client->request(method: 'POST', uri: '/api/simulations', content: $xml);

		$response = $this->client->getResponse();
		self::assertSame(expected: 400, actual: $response->getStatusCode());
	}

	#[Test]
	public function createOrganismOutOfBounds(): void
	{
		$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<life>
  <world>
    <dimension>10</dimension>
    <speciesCount>2</speciesCount>
    <iterationsCount>5</iterationsCount>
  </world>
  <organisms>
    <organism><x_pos>1</x_pos><y_pos>1</y_pos><species>A</species></organism>
    <organism><x_pos>1</x_pos><y_pos>2</y_pos><species>A</species></organism>
    <organism><x_pos>2</x_pos><y_pos>1</y_pos><species>A</species></organism>
    <organism><x_pos>2</x_pos><y_pos>2</y_pos><species>A</species></organism>
    <organism><x_pos>999</x_pos><y_pos>999</y_pos><species>A</species></organism>
  </organisms>
</life>
XML;

		$this->client->request(method: 'POST', uri: '/api/simulations', content: $xml);

		$response = $this->client->getResponse();
		self::assertSame(expected: 400, actual: $response->getStatusCode());
	}

	private function buildXmlPayload(): string
	{
		return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<life>
  <world>
    <dimension>10</dimension>
    <speciesCount>2</speciesCount>
    <iterationsCount>5</iterationsCount>
  </world>
  <organisms>
    <organism><x_pos>1</x_pos><y_pos>1</y_pos><species>A</species></organism>
    <organism><x_pos>1</x_pos><y_pos>2</y_pos><species>A</species></organism>
    <organism><x_pos>2</x_pos><y_pos>1</y_pos><species>B</species></organism>
    <organism><x_pos>2</x_pos><y_pos>2</y_pos><species>B</species></organism>
  </organisms>
</life>
XML;
	}

	private function buildXmlPayloadWithOverrides(int $dimension = 10, int $iterationsCount = 5): string
	{
		return sprintf(
			<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<life>
  <world>
    <dimension>%d</dimension>
    <speciesCount>2</speciesCount>
    <iterationsCount>%d</iterationsCount>
  </world>
  <organisms>
    <organism><x_pos>1</x_pos><y_pos>1</y_pos><species>A</species></organism>
    <organism><x_pos>1</x_pos><y_pos>2</y_pos><species>A</species></organism>
    <organism><x_pos>2</x_pos><y_pos>1</y_pos><species>B</species></organism>
    <organism><x_pos>2</x_pos><y_pos>2</y_pos><species>B</species></organism>
  </organisms>
</life>
XML
			,
			$dimension,
			$iterationsCount,
		);
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
