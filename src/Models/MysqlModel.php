<?php

namespace phpDM\Models;

use \phpDM\Models\FieldFactories;

class MysqlModel extends BaseModel
{

	protected static $type = 'mysql';
	static protected $_primaryKey = 'id';
	protected $_fieldFactory = FieldFactories\MysqlFieldFactory::class;

	protected function addSoftDeleteWhere($queryBuilder) {
		if (($key = array_search('deletedTimestamp', $this->fields)) !== false) {
			$queryBuilder->where($key, null);
		}
		return $queryBuilder;
	}

	public function first() {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		$queryBuilder = new $queryBuilder(static::$connection ?: null);
		$queryBuilder->setHydrate(static::class);
		$queryBuilder = static::addSoftDeleteWhere($queryBuilder);
		$return = $queryBuilder
			->table(static::getTableName())
			->select(array_keys($this->fields))
			->limit(1)
			->get();
		return $return;
	}

	public function find($id) {
		if (!isset(static::$primaryKey)) {
			return null;
		}
		$primaryKey = static::$primaryKey;
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		$queryBuilder = new $queryBuilder(static::$connection ?: null);
		$queryBuilder->setHydrate(static::class);
		$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys($this->fields));
		$queryBuilder->where($primaryKey, $id);
		$queryBuilder = static::addSoftDeleteWhere($queryBuilder);
		$result = $queryBuilder->first();
		return $result;
	}

	private function updateOneOnPrimaryKey($data) {
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
		} elseif ($this->_new) {
			$connectionFactory = \phpDM\Connections\ConnectionFactory::getInstance();
			$queryBuilder = $connectionFactory->getQueryBuilder(static::$type);
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