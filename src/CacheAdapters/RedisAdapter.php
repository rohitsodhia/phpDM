<?php

namespace phpDM\CacheAdapters;

use phpDM\Models\BaseModel;

class RedisAdapter extends CacheAdapter
{

	protected static $type = 'redis';
	protected static $validOptions = [
		'namespace',
		'separator',
		'ttl',
	];
	protected static $defaultOptions = [
		'namespace' => '',
		'separator' => ':',
		'ttl' => null,
	];

	private function getModelKey($modelName, $id) {
		return ($this->getOption('namespace') ? $this->getOption('namespace') . $this->getOption('separator') : '') . $modelName . $this->getOption('separator') . $id;
	}

	public function storeModels(array $models)
	{
		$connection = $this->connection->getConnection();
		foreach ($models as $model) {
			$key = $this->getModelKey($model->getTableName(), $model->getPrimaryKey());
			$connection->set($key, json_encode($model), $this->getOption('ttl'));
		}
	}

	public function getModel(string $model, $id)
	{
		$id = (string) $id;
		$connection = $this->connection->getConnection();
		$key = $this->getModelKey($model::getTableName(), $id);
		if ($connection->exists($key)) {
			$data = $connection->get($key);
			$data = json_decode($data);
			return $model::hydrate($data);
		} else {
			return null;
		}
	}

	public function getModels(string $model, $ids)
	{
		$connection = $this->connection->getConnection();
		$models = [];
		$ids = array_map(function ($id) { return (string) $id; }, $ids);
		$keys = array_map(function ($id) use ($model) { return $this->getModelKey($model::getTableName(), $id); }, $ids);
		$data = $connection->mGet($keys);
		foreach ($data as $iData) {
			if ($iData) {
				$iData = json_decode($iData);
				$models[] = $model::hydrate($iData);
			}
		}
		return $models;
	}

}