<?php

declare(strict_types=1);

namespace App\Simulation;

use App\Simulation\Exception\SimulationNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Simulation>
 */
final class SimulationRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct(registry: $registry, entityClass: Simulation::class);
	}

	/**
	 * @throws SimulationNotFoundException
	 */
	public function get(int $id): Simulation
	{
		$simulation = $this->find(id: $id);

		if (!$simulation instanceof Simulation) {
			throw SimulationNotFoundException::withId(id: $id);
		}

		return $simulation;
	}

	/**
	 * @throws ORMException
	 * @throws UniqueConstraintViolationException
	 */
	public function save(Simulation $simulation): void
	{
		$this->getEntityManager()
			->persist(object: $simulation);
		$this->getEntityManager()
			->flush();
	}
}
