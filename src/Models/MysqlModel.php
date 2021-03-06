<?php

namespace phpDM\Models;

use \phpDM\Models\FieldFactories;

class MysqlModel extends BaseModel
{

	protected static $type = 'mysql';
	protected $_defaultPrimaryKey = 'id';
	protected $_fieldFactory = FieldFactories\MysqlFieldFactory::class;

	public function addSoftDeleteWhere($queryBuilder) {
		if (array_key_exists('deletedTimestamp', $this->_specialFields)) {
			$queryBuilder->where($this->_specialFields['deletedTimestamp'], null);
		}
		return $queryBuilder;
	}

	public static function first() {
		$model = new static();
		$connectionFactory = \phpDM\Connections\ConnectionFactory::getInstance();
		$queryBuilderClass = $connectionFactory->getQueryBuilder(static::$type);
		$queryBuilder = new $queryBuilderClass(static::$connection ?: null);
		$queryBuilder = $model->addSoftDeleteWhere($queryBuilder);
		$result = $queryBuilder
			->table($model->getTableName())
			->select($model->getFieldNames())
			->first();
		$model->hydrate($result);
		return $model;
	}

	public static function find($id) {
		$model = new static();
		if (!isset($model->_specialFields['primaryKey'])) {
			return null;
		}
		$connectionFactory = \phpDM\Connections\ConnectionFactory::getInstance();
		$queryBuilderClass = $connectionFactory->getQueryBuilder(static::$type);
		$queryBuilder = new $queryBuilderClass(static::$connection ?: null);
		$queryBuilder
			->table($model->getTableName())
			->select($model->getFieldNames())
			->where($model->getSpecialField('primaryKey'), $id);
		$queryBuilder = $model->addSoftDeleteWhere($queryBuilder);
		$result = $queryBuilder->first();
		if ($result === null) {
			return false;
		}
		$model->hydrate($result);
		return $model;
	}

	private function updateOneOnPrimaryKey($data) {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		$queryBuilder = new $queryBuilder(static::$connection ?: null);
		$return = $queryBuilder
			->table($this->getTableName())
			->where(static::$primaryKey, $this->{static::$primaryKey})
			->limit(1)
			->update($data);
		return $return ? $queryBuilder->rowCount() : null;
	}

	public function save() {
		$curTime = new \Carbon\Carbon();
		if (!$this->_new && $this->_data[$this->_specialFields['primaryKey']]->get()) {
			$this->addTimestamps($curTime);
			$changedData = $this->getData(true);
			$return = $this->updateOneOnPrimaryKey($changedData);
			return $return;
		} elseif ($this->_new) {
			$connectionFactory = \phpDM\Connections\ConnectionFactory::getInstance();
			$queryBuilder = $connectionFactory->getQueryBuilder(static::$type);
			$this->addTimestamps($curTime);
			$data = $this->getData();
			$queryBuilder = new $queryBuilder(static::$connection);
			$success = $queryBuilder->table($this->getTableName())->insert($data);
			if ($success === true) {
				$this->_data[$this->_specialFields['primaryKey']]->set($queryBuilder->lastInsertId());
				return $success;
			} else {
				throw new \Exception('Did not save: ' . $success[2]);
			}
		}
	}
	
}