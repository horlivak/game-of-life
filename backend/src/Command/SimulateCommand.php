<?php

declare(strict_types=1);

namespace App\Command;

use App\Engine\ConflictResolverInterface;
use App\Engine\Grid;
use App\Engine\SimulationEngine;
use App\Infrastructure\Xml\XmlDeserializer;
use App\Infrastructure\Xml\XmlSerializer;
use App\Simulation\SimulationInput;
use App\Simulation\SimulationResult;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'app:simulate', description: 'Run Game of Life simulation from an XML input file',)]
final class SimulateCommand extends Command
{
	public function __construct(
		private readonly ConflictResolverInterface $conflictResolver,
		private readonly SimulationEngine $engine,
		private readonly XmlDeserializer $xmlDeserializer,
		private readonly XmlSerializer $xmlSerializer,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this
			->addArgument(name: 'input', mode: InputArgument::REQUIRED, description: 'Path to XML input file');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle(input: $input, output: $output);

		$inputPath = $input->getArgument(name: 'input');
		if (!is_string(value: $inputPath)) {
			$io->error(message: 'Input argument must be a string.');

			return Command::FAILURE;
		}

		if (!file_exists(filename: $inputPath)) {
			$io->error(message: sprintf('Input file not found: %s', $inputPath));

			return Command::FAILURE;
		}

		$content = file_get_contents(filename: $inputPath);
		if ($content === false) {
			$io->error(message: sprintf('Cannot read file: %s', $inputPath));

			return Command::FAILURE;
		}

		try {
			$request = $this->xmlDeserializer->deserialize(xml: $content);
		} catch (Throwable $throwable) {
			$io->error(message: $throwable->getMessage());

			return Command::FAILURE;
		}

		try {
			$grid = $this->buildGrid(request: $request);
			$iterations = $request->iterations;
			$speciesTypes = $request->speciesTypes;

			$io->info(message: sprintf(
				'World: %dx%d, Species: %d, Organisms: %d, Iterations: %d',
				$grid->size,
				$grid->size,
				count(value: $speciesTypes),
				count(value: $grid->cells),
				$iterations,
			));

			$resultGrid = $this->engine->simulate(grid: $grid, iterations: $iterations);
			$this->renderGrid(io: $io, grid: $resultGrid);

			$result = new SimulationResult(grid: $resultGrid, speciesTypes: $speciesTypes, iterationsCount: $iterations);
			$xml = $this->xmlSerializer->render(result: $result);

			$io->newLine();
			$output->writeln(messages: $xml);
		} catch (Throwable $throwable) {
			$io->error(message: $throwable->getMessage());

			return Command::FAILURE;
		}

		$io->success(message: sprintf(
			'Simulation complete. %d organisms remaining.',
			count(value: $resultGrid->cells),
		));

		return Command::SUCCESS;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function buildGrid(SimulationInput $request): Grid
	{
		$resolved = $this->conflictResolver->resolve(cells: $request->cells);

		return Grid::fromCells(size: $request->dimension, cells: $resolved);
	}

	private function renderGrid(SymfonyStyle $io, Grid $grid): void
	{
		$io->section(message: 'Grid:');

		$rows = [];

		for ($y = 0; $y < $grid->size; $y++) {
			$row = '';

			for ($x = 0; $x < $grid->size; $x++) {
				$cell = $grid->getCellAtCoords(x: $x, y: $y);
				$row .= $cell !== null ? mb_strtoupper(
					string: mb_substr(string: $cell->species->type, start: 0, length: 1)
				) : '.';
			}

			$rows[] = $row;
		}

		$io->text(message: $rows);
	}
}
