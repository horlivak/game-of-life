<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\DBAL\Connection;
use Redis;

trait DatabaseResetTrait
{
	private function resetDatabase(): void
	{
		/** @var Connection $connection */
		$connection = self::getContainer()->get(id: Connection::class);
		$connection->executeStatement(sql: 'TRUNCATE TABLE simulations RESTART IDENTITY CASCADE');
	}

	private function resetRedis(): void
	{
		/** @var Redis $redis */
		$redis = self::getContainer()->get(id: Redis::class);
		$redis->flushDB();
	}
}
