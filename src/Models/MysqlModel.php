<?php

namespace phpDM\Models;

class MysqlModel extends BaseModel
{

	public static $type = 'mysql';

	public static function castValue(string $cast, $value) {
		if ($possibleValue = parent::castValue($cast, $value)) {
			return $possibleValue;
		}
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
		$result = $queryBuilder->where($primaryKey, $id)->first();
		return $result;
	}

	public function save() {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		if (!$this->new && $this->data[static::$primaryKey]) {
			$changedData = $this->getChangedFields();
			$queryBuilder = new $queryBuilder(static::$connection ?: null);
			$return = $queryBuilder
				->table(static::getTablenName())
				->where(static::$primaryKey, $this->{static::$primaryKey})
				->update($changedData);
			if ($return->getMatchedCount() !== 0) {
				return $return;
			}
		} elseif ($this->new) {
			$data = $this->getFields();
			$queryBuilder = new $queryBuilder(static::$connection ?: null);
			$queryBuilder->table(static::getTableName())->insert($data);
		}
	}

	
}