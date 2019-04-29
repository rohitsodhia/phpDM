<?php

namespace phpDM\Models;

use phpDM\Connections\ConnectionFactory;

class MongoModel extends BaseModel
{

	private const TYPE = 'mongo';
	static protected $primaryKey = '_id';

	public static function castValue(string $cast, $value)
	{
		if (($possibleValue = parent::castValue($cast, $value)) !== null) {
			return $possibleValue;
		} elseif (in_array($cast, ['timestamp', 'datetime', 'createdTimestamp', 'updatedTimestamp', 'deletedTimestamp'])) {
			if ($value instanceof \MongoDB\BSON\UTCDateTime) {
				return \Carbon\Carbon::instance($value->toDateTime());
			}
		} elseif ($cast === 'mongoId') {
			if (is_string($value)) {
				$value = new \MongoDB\BSON\ObjectId($value);
			} elseif (get_class($value) === 'stdClass' && strlen($value->{'$oid'})) {
				$value = new \MongoDB\BSON\ObjectId($value->{'$oid'});
			} elseif ($value !== null && get_class($value) !== 'MongoDB\BSON\ObjectId') {
				throw new \Exception('No valid id');
			}
			return $value;
		}
	}

	protected static function clone($value)
	{
		if (is_object($value) && get_class($value) === 'MongoDB\BSON\ObjectId') {
			return new \MongoDB\BSON\ObjectId((string) $value);
		}
		return clone $value;
	}

	public function getPrimaryKey()
	{
		return $this->data['_id'];
	}

	public static function getTableName()
	{
		if (isset(static::$collection)) {
			return static::$collection;
		}
		return parent::getTableName();
	}

	protected static function getCollectionName()
	{
		return static::getTableName();
	}

	public static function addSoftDeleteWhere($queryBuilder)
	{
		if (($key = array_search('deletedTimestamp', static::$fields)) !== false) {
			$queryBuilder->where($key, null);
		}
		return $queryBuilder;
	}

	public static function first()
	{
		try {
			$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(self::TYPE);
		} catch (\Exception $e) {
			die('No query builder found');
		}

		$queryBuilder = new $queryBuilder(static::$connection ?: '');
		$queryBuilder = static::addSoftDeleteWhere($queryBuilder);
		$return = $queryBuilder
			->table(static::getTableName())
			->select(array_keys(static::$fields));
		if (isset($first)) {
			$return = $return->where('_id', $first->_id);
		} else {
			$return = $return->limit(1);
		}
		$return = $return->get();

		return $return;
	}

	public static function find($id)
	{
		if (!isset(static::$primaryKey)) {
			return null;
		}
		$primaryKey = static::$primaryKey;
		if (static::$fields[$primaryKey] === 'mongoId' && gettype($id) === 'string') {
			$id = new \MongoDB\BSON\ObjectId($id);
		}
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(self::TYPE);
		$queryBuilder = new $queryBuilder(static::$connection ?: '');
		$queryBuilder->setHydrate(static::class);
		$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys(static::$fields));
		$queryBuilder = static::addSoftDeleteWhere($queryBuilder);
		$result = $queryBuilder->where($primaryKey, $id)->first();
		return $result;
	}

	public function save()
	{
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(self::TYPE);
		if (!$this->new && $this->data[static::$primaryKey]) {
			$curTime = new \Carbon\Carbon();
			$this->addTimestamps($curTime);
			$changedData = $this->getData();
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
			$data = $this->getData();
			$queryBuilder = new $queryBuilder(static::$connection ?: null);
			$success = $queryBuilder->collection(static::getCollectionName())->insert($data);
			if ($success !== false) {
				return $success;
			}
		}
	}

}
