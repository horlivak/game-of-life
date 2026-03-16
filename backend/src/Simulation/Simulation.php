<?php

declare(strict_types=1);

namespace App\Simulation;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use LogicException;

#[ORM\Entity(repositoryClass: SimulationRepository::class)]
#[ORM\Table(name: 'simulations')]
class Simulation
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 20, enumType: SimulationStatus::class)]
	private SimulationStatus $status = SimulationStatus::Pending;

	#[ORM\Version]
	#[ORM\Column(type: Types::INTEGER)]
	private int $version = 1;

	#[ORM\Column]
	private DateTimeImmutable $createdAt;

	#[ORM\Column(nullable: true)]
	private ?DateTimeImmutable $completedAt = null;

	/**
	 * @param list<string> $speciesTypes
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		#[ORM\Column]
		private int $dimension,
		#[ORM\Column]
		private int $speciesCount,
		#[ORM\Column]
		private int $iterationsCount,
		#[ORM\Column(type: Types::JSON)]
		private array $speciesTypes,
	) {
		if ($dimension < 1) {
			throw new InvalidArgumentException(message: 'Dimension must be at least 1.');
		}

		if ($speciesCount < 1) {
			throw new InvalidArgumentException(message: 'Species count must be at least 1.');
		}

		if ($iterationsCount < 0) {
			throw new InvalidArgumentException(message: 'Iterations count must be non-negative.');
		}

		if ($speciesTypes === []) {
			throw new InvalidArgumentException(message: 'Species types must not be empty.');
		}

		$this->createdAt = new DateTimeImmutable();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getDimension(): int
	{
		return $this->dimension;
	}

	public function getSpeciesCount(): int
	{
		return $this->speciesCount;
	}

	public function getIterationsCount(): int
	{
		return $this->iterationsCount;
	}

	public function getStatus(): SimulationStatus
	{
		return $this->status;
	}

	/**
	 * @return list<string>
	 */
	public function getSpeciesTypes(): array
	{
		return $this->speciesTypes;
	}

	public function getCreatedAt(): DateTimeImmutable
	{
		return $this->createdAt;
	}

	public function getVersion(): int
	{
		return $this->version;
	}

	public function getCompletedAt(): ?DateTimeImmutable
	{
		return $this->completedAt;
	}

	/**
	 * @throws LogicException
	 */
	public function markRunning(): void
	{
		if ($this->status !== SimulationStatus::Pending) {
			throw new LogicException(message: sprintf(
				'Cannot mark simulation as running from state "%s".',
				$this->status->value
			));
		}

		$this->status = SimulationStatus::Running;
	}

	/**
	 * @throws LogicException
	 */
	public function markCompleted(): void
	{
		if ($this->status !== SimulationStatus::Running) {
			throw new LogicException(
				message: sprintf('Cannot mark simulation as completed from state "%s".', $this->status->value),
			);
		}

		$this->status = SimulationStatus::Completed;
		$this->completedAt = new DateTimeImmutable();
	}

	/**
	 * @throws LogicException
	 */
	public function markFailed(): void
	{
		if ($this->status !== SimulationStatus::Pending && $this->status !== SimulationStatus::Running) {
			throw new LogicException(message: sprintf(
				'Cannot mark simulation as failed from state "%s".',
				$this->status->value
			));
		}

		$this->status = SimulationStatus::Failed;
		$this->completedAt = new DateTimeImmutable();
	}
}
