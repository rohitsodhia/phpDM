<?php

namespace phpDM\Models;

class MongoModel extends BaseModel
{

	public static $type = 'mongo';
	static protected $primaryKey = '_id';

	public static function castValue(string $cast, $value) {
		if ($possibleValue = parent::castValue($cast, $value)) {
			return $possibleValue;
		} elseif ($cast === 'timestamp') {
			if ($value instanceof \MongoDB\BSON\UTCDateTime) {
				return \Carbon\Carbon::instance($value->toDateTime());
			}
		} elseif ($cast === 'mongoId') {
			if (is_string($value)) {
				$value = new \MongoDB\BSON\ObjectId($value);
			}
			return $value;
		}
	}

	protected static function getTableName() {
		if (isset(static::$collection)) {
			return static::$collection;
		}
		return parent::getTableName();
	}

	protected static function getCollectionName() {
		return static::getTableName();
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
		$queryBuilder = static::addSoftDeleteWhere($queryBuilder);
		$result = $queryBuilder->where($primaryKey, $id)->first();
		return $result;
	}

	public function save() {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		if (!$this->new && $this->data[static::$primaryKey]) {
			$curTime = new \Carbon\Carbon();
			$this->addTimestamps($curTime);
			$changedData = $this->getChangedFields();
			$queryBuilder = new $queryBuilder(static::$connection ?: null);
			$return = $queryBuilder
				->collection(static::getCollectionName())
				->where(static::$primaryKey, $this->{static::$primaryKey})
				->update($changedData);
			if ($return->getMatchedCount() !== 0) {
				return $return;
			}
		} elseif ($this->new) {
			if (
				(isset(static::$primaryKey) && static::$primaryKey !== null && (!isset($this->data[static::$primaryKey]) || $this->data[static::$primaryKey] === null)) ||
				(!isset(static::$primaryKey) && $this->data['_id'] === null)
			) {
				$this->data[static::$primaryKey ?: '_id'] = new \MongoDB\BSON\ObjectId();
			}
			$curTime = new \Carbon\Carbon();
			$this->addTimestamps($curTime);
			$data = $this->getFields();
			$queryBuilder = new $queryBuilder(static::$connection ?: null);
			$success = $queryBuilder->collection(static::getCollectionName())->insert($data);
			if ($success !== false) {
				return $success;
			}
		}
	}


}
