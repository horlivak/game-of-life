<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Engine\Cell;
use App\Engine\Grid;
use App\Engine\Position;
use App\Engine\Species;
use App\Infrastructure\GridStore\RedisGridStore;
use App\Simulation\Simulation;
use App\Simulation\SimulationRepository;
use App\Tests\Functional\DatabaseResetTrait;
use DOMDocument;
use DOMNode;
use DOMXPath;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetSimulationResultControllerTest extends WebTestCase
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
	public function getResultSuccess(): void
	{
		$simulation = $this->createCompletedSimulation();
		$simulationId = $simulation->getId();
		self::assertNotNull(actual: $simulationId);

		$this->saveGridInRedis(simulationId: $simulationId);

		$this->client->request(method: 'GET', uri: '/api/simulations/' . $simulationId . '/result');

		$response = $this->client->getResponse();
		self::assertSame(expected: 200, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		self::assertNotNull(actual: $this->xpathValue(xpath: $xpath, expression: '/life/world/dimension'));
		self::assertNotNull(actual: $this->xpathValue(xpath: $xpath, expression: '/life/world/speciesCount'));

		$organisms = $xpath->query(expression: '/life/organisms/organism');
		self::assertNotFalse(condition: $organisms);
		self::assertGreaterThan(minimum: 0, actual: $organisms->length);
	}

	#[Test]
	public function getResultNotFound(): void
	{
		$this->client->request(method: 'GET', uri: '/api/simulations/99999/result');

		$response = $this->client->getResponse();
		self::assertSame(expected: 404, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		self::assertSame(
			expected: 'Simulation not found',
			actual: $this->xpathValue(xpath: $xpath, expression: '/error/message')
		);
	}

	#[Test]
	public function getResultNotCompleted(): void
	{
		$simulation = $this->createPendingSimulation();

		$this->client->request(method: 'GET', uri: '/api/simulations/' . $simulation->getId() . '/result');

		$response = $this->client->getResponse();
		self::assertSame(expected: 202, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		self::assertSame(
			expected: 'Simulation not completed yet',
			actual: $this->xpathValue(xpath: $xpath, expression: '/error/message')
		);
	}

	#[Test]
	public function getResultFailedSimulation(): void
	{
		$simulation = $this->createFailedSimulation();

		$this->client->request(method: 'GET', uri: '/api/simulations/' . $simulation->getId() . '/result');

		$response = $this->client->getResponse();
		self::assertSame(expected: 422, actual: $response->getStatusCode());

		$xpath = $this->parseXml(xml: (string) $response->getContent());
		self::assertSame(
			expected: 'Simulation failed',
			actual: $this->xpathValue(xpath: $xpath, expression: '/error/message')
		);
	}

	private function createFailedSimulation(): Simulation
	{
		$simulation = new Simulation(dimension: 10, speciesCount: 2, iterationsCount: 5, speciesTypes: ['A', 'B']);
		$simulation->markRunning();
		$simulation->markFailed();

		/** @var SimulationRepository $repository */
		$repository = self::getContainer()->get(id: SimulationRepository::class);
		$repository->save(simulation: $simulation);

		return $simulation;
	}

	private function createCompletedSimulation(): Simulation
	{
		$simulation = new Simulation(dimension: 10, speciesCount: 2, iterationsCount: 5, speciesTypes: ['A', 'B']);
		$simulation->markRunning();
		$simulation->markCompleted();

		/** @var SimulationRepository $repository */
		$repository = self::getContainer()->get(id: SimulationRepository::class);
		$repository->save(simulation: $simulation);

		return $simulation;
	}

	private function createPendingSimulation(): Simulation
	{
		$simulation = new Simulation(dimension: 10, speciesCount: 2, iterationsCount: 5, speciesTypes: ['A', 'B']);

		/** @var SimulationRepository $repository */
		$repository = self::getContainer()->get(id: SimulationRepository::class);
		$repository->save(simulation: $simulation);

		return $simulation;
	}

	private function saveGridInRedis(int $simulationId): void
	{
		$grid = new Grid(size: 10, cells: [
			'1:1' => new Cell(position: new Position(x: 1, y: 1), species: new Species(type: 'A')),
			'2:2' => new Cell(position: new Position(x: 2, y: 2), species: new Species(type: 'B')),
		]);

		/** @var RedisGridStore $gridStore */
		$gridStore = self::getContainer()->get(id: RedisGridStore::class);
		$gridStore->save(simulationId: $simulationId, grid: $grid);
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
