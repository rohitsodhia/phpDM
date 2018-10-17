<?php

namespace phpDM\Models;

class MysqlModel extends BaseModel
{

	public static $type = 'mysql';
	static protected $primaryKey = 'id';

	public static function castValue(string $cast, $value) {
		if (($possibleValue = parent::castValue($cast, $value)) !== null) {
			return $possibleValue;
		}
	}

	public static function addSoftDeleteWhere($queryBuilder) {
		if (($key = array_search('deletedTimestamp', static::$fields)) !== false) {
			$queryBuilder->where($key, null);
		}
		return $queryBuilder;
	}

	public static function first() {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		$queryBuilder = new $queryBuilder(static::$connection ?: null);
		$queryBuilder->setHydrate(static::class);
		$queryBuilder = static::addSoftDeleteWhere($queryBuilder);
		$return = $queryBuilder
			->table(static::getTableName())
			->select(array_keys(static::$fields))
			->limit(1)
			->get();
		return $return;
	}

	public static function find($id) {
		if (!isset(static::$primaryKey)) {
			return null;
		}
		$primaryKey = static::$primaryKey;
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		$queryBuilder = new $queryBuilder(static::$connection ?: null);
		$queryBuilder->setHydrate(static::class);
		$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys(static::$fields));
		$queryBuilder->where($primaryKey, $id);
		$queryBuilder = static::addSoftDeleteWhere($queryBuilder);
		$result = $queryBuilder->first();
		return $result;
	}

	public function updateOneOnPrimaryKey($data) {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		$queryBuilder = new $queryBuilder(static::$connection ?: null);
		$return = $queryBuilder
			->table(static::getTableName())
			->where(static::$primaryKey, $this->{static::$primaryKey})
			->limit(1)
			->update($data);
		return $return ? $queryBuilder->rowCount() : null;
	}

	public function save() {
		$curTime = new \Carbon\Carbon();
		if (!$this->new && $this->data[static::$primaryKey]) {
			$this->addTimestamps($curTime);
			$changedData = $this->getChangedFields();
			$return = $this->updateOneOnPrimaryKey($changedData);
			return $return;
		} elseif ($this->new) {
			$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
			$this->addTimestamps($curTime);
			$data = $this->getData();
			$queryBuilder = new $queryBuilder(static::$connection ?: null);
			$success = $queryBuilder->table(static::getTableName())->insert($data);
			if ($success !== false) {
				$this->data[static::$primaryKey] = $queryBuilder->lastInsertId();
				return $success;
			}
		}
	}
	
}