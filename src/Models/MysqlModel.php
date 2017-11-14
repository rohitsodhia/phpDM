<?php

namespace phpDM\Models;

class MysqlModel extends BaseModel
{

	public static $type = 'mysql';

	protected static function castValue(string $cast, $value) {
		if ($possibleValue = parent::castValue($cast, $value)) {
			return $possibleValue;
		} elseif ($cast === 'mongoId') {
			return $value;
		}
	}

	public static function find($id) {
		if (!isset(static::$primaryKey)) {
			return null;
		}
		$primaryKey = static::$primaryKey;
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		$queryBuilder = new $queryBuilder();
		$queryBuilder->setHydrate(static::class);
		$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys(static::$fields));
		$result = $queryBuilder->where($primaryKey, $id)->first();
		return $result;
	}

	public function save() {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		if ($this->data[static::$primaryKey] || in_array(static::$primaryKey, $this->changed[])) {
			$changedData = [];
			foreach ($this->changed as $field) {
				$changedData[$field] = $this->data[$field];
			}
			$query = new $queryBuilder();
			$return = $query
				->collection(static::getCollectionName())
				->where(static::$primaryKey, $this->{static::$primaryKey})
				->update($changedData);
			if ($return->getMatchedCount() !== 0) {
				return $return;
			}
		}

		$query = new $queryBuilder();
		$query->collection(static::getCollectionName())->insert($this->data);
	}

	
}