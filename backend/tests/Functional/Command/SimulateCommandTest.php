<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SimulateCommandTest extends KernelTestCase
{
	private CommandTester $commandTester;

	protected function setUp(): void
	{
		$application = new Application(kernel: self::bootKernel());
		$command = $application->find(name: 'app:simulate');
		$this->commandTester = new CommandTester(command: $command);
	}

	#[Test]
	public function success(): void
	{
		$inputPath = dirname(path: __DIR__, levels: 3) . '/fixtures/input.xml';

		$this->commandTester->execute(input: [
			'input' => $inputPath,
		]);

		self::assertSame(expected: 0, actual: $this->commandTester->getStatusCode());

		$output = $this->commandTester->getDisplay();
		self::assertStringContainsString(needle: 'Simulation complete', haystack: $output);
		self::assertStringContainsString(needle: '<life>', haystack: $output);
	}

	#[Test]
	public function missingFile(): void
	{
		$this->commandTester->execute(input: [
			'input' => '/nonexistent/file.xml',
		]);

		self::assertSame(expected: 1, actual: $this->commandTester->getStatusCode());

		$output = $this->commandTester->getDisplay();
		self::assertStringContainsString(needle: 'not found', haystack: $output);
	}
}
