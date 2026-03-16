<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Simulation\Simulation;
use App\Simulation\SimulationRepository;
use App\Tests\Functional\DatabaseResetTrait;
use DOMDocument;
use DOMNode;
use DOMXPath;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetSimulationStatusControllerTest extends WebTestCase
{
	use DatabaseResetTrait;

	private KernelBrowser $client;

	protected function setUp(): void
	{
		$this->client = self::createClient();
		$this->resetDatabase();
	}

	#[Test]
	public function getStatusSuccess(): void
	{
		$simulation = $this->createSimulationInDb();

		$this->client->request(method: 'GET', uri: '/api/simulations/' . $simulation->getId() . '/status');

		$response = $this->client->getResponse();
		self::assertSame(expected: 200, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		self::assertSame(
			expected: (string) $simulation->getId(),
			actual: $this->xpathValue(xpath: $xpath, expression: '/simulation/id')
		);
		self::assertSame(expected: 'pending', actual: $this->xpathValue(xpath: $xpath, expression: '/simulation/status'));
		self::assertSame(expected: '10', actual: $this->xpathValue(xpath: $xpath, expression: '/simulation/dimension'));
		self::assertSame(expected: '2', actual: $this->xpathValue(xpath: $xpath, expression: '/simulation/speciesCount'));
		self::assertSame(expected: '5', actual: $this->xpathValue(xpath: $xpath, expression: '/simulation/iterationsCount'));
		self::assertNotNull(actual: $this->xpathValue(xpath: $xpath, expression: '/simulation/createdAt'));
		self::assertNull(actual: $this->xpathValue(xpath: $xpath, expression: '/simulation/completedAt'));
	}

	#[Test]
	public function getStatusNotFound(): void
	{
		$this->client->request(method: 'GET', uri: '/api/simulations/99999/status');

		$response = $this->client->getResponse();
		self::assertSame(expected: 404, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		self::assertSame(
			expected: 'Simulation not found',
			actual: $this->xpathValue(xpath: $xpath, expression: '/error/message')
		);
	}

	private function createSimulationInDb(): Simulation
	{
		$simulation = new Simulation(dimension: 10, speciesCount: 2, iterationsCount: 5, speciesTypes: ['A', 'B']);

		/** @var SimulationRepository $repository */
		$repository = self::getContainer()->get(id: SimulationRepository::class);
		$repository->save(simulation: $simulation);

		return $simulation;
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
