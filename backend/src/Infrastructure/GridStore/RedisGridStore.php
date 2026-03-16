<?php

declare(strict_types=1);

namespace App\Infrastructure\GridStore;

use App\Engine\Cell;
use App\Engine\Grid;
use App\Engine\Position;
use App\Engine\Species;
use App\Infrastructure\GridStore\Exception\GridStoreException;
use App\Infrastructure\Serializer\Exception\UnresolvableParameterException;
use App\Infrastructure\Serializer\MappingUtils;
use InvalidArgumentException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Redis;

final readonly class RedisGridStore implements GridStoreInterface
{
	private const int TTL_SECONDS = 86_400; // 24 hours

	public function __construct(
		private Redis $redis,
	) {
	}

	/**
	 * @throws GridStoreException
	 * @throws JsonException
	 */
	public function save(int $simulationId, Grid $grid): void
	{
		$data = $this->serialize(grid: $grid);
		$result = $this->redis->setex(key: $this->key(simulationId: $simulationId), expire: self::TTL_SECONDS, value: $data);

		if ($result !== true) {
			throw GridStoreException::writeFailed(simulationId: $simulationId);
		}
	}

	/**
	 * @throws GridStoreException
	 * @throws JsonException
	 * @throws UnresolvableParameterException
	 * @throws InvalidArgumentException
	 */
	public function load(int $simulationId): ?Grid
	{
		$data = $this->redis->get(key: $this->key(simulationId: $simulationId));

		if (!\is_string(value: $data)) {
			return null;
		}

		return $this->deserialize(data: $data, simulationId: $simulationId);
	}

	public function delete(int $simulationId): void
	{
		$this->redis->del(key: $this->key(simulationId: $simulationId));
	}

	private function key(int $simulationId): string
	{
		return sprintf('simulation:%d:grid', $simulationId);
	}

	/**
	 * @throws JsonException
	 */
	private function serialize(Grid $grid): string
	{
		$cells = [];

		foreach ($grid->cells as $cell) {
			$cells[] = [$cell->position->x, $cell->position->y, $cell->species->type];
		}

		return Json::encode(value: [
			'size' => $grid->size,
			'organisms' => $cells,
		]);
	}

	/**
	 * @throws GridStoreException
	 * @throws JsonException
	 * @throws UnresolvableParameterException
	 * @throws InvalidArgumentException
	 */
	private function deserialize(string $data, int $simulationId): Grid
	{
		/** @var mixed[] $decoded */
		$decoded = Json::decode(json: $data, forceArrays: true);

		$cells = [];
		foreach (MappingUtils::extractArray(data: $decoded, key: 'organisms') as $org) {
			if (!is_array(value: $org) || count(value: $org) !== 3) {
				throw GridStoreException::invalidData(simulationId: $simulationId);
			}

			[$x, $y, $type] = $org;

			if (!is_int(value: $x) || !is_int(value: $y) || !is_string(value: $type)) {
				throw GridStoreException::invalidData(simulationId: $simulationId);
			}

			$position = new Position(x: $x, y: $y);
			$cell = new Cell(position: $position, species: new Species(type: $type));
			$cells[$position->key()] = $cell;
		}

		return new Grid(size: MappingUtils::extractInt(data: $decoded, key: 'size'), cells: $cells);
	}
}
