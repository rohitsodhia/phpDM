<?php

namespace phpDM\QueryBuilder;

class MongoQueryBuilder extends QueryBuilder
{

	protected static $type = 'mongo';
	protected $conditions = [];

	public function collection(string $collection) {
		return $this->table($collection);
	}

	protected static function buildCondition($val1, $val2, $comparitor = '=') {
		if ($comparitor === '=') {
			return [$val1 => $val2];
		}
		switch ($comparitor) {
			case '>':
				return [$val1 => ['$gt' => $val2]];
			case '>=':
				return [$val1 => ['$gte' => $val2]];
			case '<':
				return [$val1 => ['$lt' => $val2]];
			case '<=':
				return [$val1 => ['$lte' => $val2]];
			case '<>':
			case '!=':
				return [$val1 => ['$ne' => $val2]];
		}
	}

	public function where() {
		$args = func_get_args();
		if (is_callable($args[0])) {
			$queryBuilder = static::class;
			$subquery = new $queryBuilder();
			$args[0]($subquery);
			$conditions = $subquery->getConditions();
		} elseif (count($args) === 2 || (count($args) === 3 && ($args[1] === '=' || $args[1] === '=='))) {
			$conditions = self::buildCondition($args[0], end($args));
		} elseif (count($args) === 3) {
			$conditions = self::buildCondition($args[0], $args[2], $args[1]);
		}
		if ($conditions) {
			if (count($this->conditions)) {
				$this->conditions = [$this->conditions, $conditions];
			} else {
				$this->conditions = $conditions;
			}
		}
		return $this;
	}

	public function orWhere() {
		$args = func_get_args();
		if (is_callable($args[0])) {
			$queryBuilder = static::class;
			$subquery = new $queryBuilder();
			$args[0]($subquery);
			$conditions = $subquery->getConditions();
		} elseif (count($args) === 2 || (count($args) === 3 && ($args[1] === '=' || $args[1] === '=='))) {
			$conditions = [$this->conditions, self::buildCondition($args[0], end($args))];
		} elseif (count($args) === 3) {
			$conditions = [$this->conditions, self::buildCondition($args[0], $args[2], $args[1])];
		}
		if ($conditions) {
			if (isset($this->conditions['$or'])) {
				$this->conditions['$or'][] = $conditions;
			} else {
				$this->conditions = ['$or' => [$this->conditions, $conditions]];
			}
		}
		return $this;
	}

	public function whereIn(string $field, array $values) {
		$this->conditions[$field] = ['$in' => $values];
		return $this;
	}

	public function sort($field, $direction = 'asc') {
		if (strtolower($direction) === 'asc') {
			$direction = 1;
		}
		if (strtolower($direction) === 'desc') {
			$direction = -1;
		}
		if ($direction === 1 || $direction === -1) {
			$this->sort[$field] = $direction;
		}
		return $this;
	}

	public function buildOptions() {
		$options = [];
		if (count($this->select)) {
			foreach ($this->select as $field) {
				$options['projection'][$field] = 1;
			}
		}
		if (count($this->sort)) {
			$options['sort'] = $this->sort;
		}
		if ($this->limit) {
			$options['limit'] = $this->limit;
		}
		if ($this->skip) {
			$options['skip'] = $this->skip;
		}
		return $options;
	}

	public function first() {
		$options = $this->buildOptions();
		$data = $this->connection->{$this->table}->findOne($this->conditions, $options);
		if (!$data) {
			return $data;
		}
		if ($this->hydrate === null) {
			return $data;
		}
		$hydrateClass = $this->hydrate;
		$obj = $hydrateClass::hydrate($data);
		return $obj;
	}

	public function get() {
		$options = $this->buildOptions();
		if ($this->limit === 1) {
			unset($options['limit']);
			$data = $this->connection->{$this->table}->findOne($this->conditions, $options);
		} else {
			$data = $this->connection->{$this->table}->find($this->conditions, $options);
		}
		if ($this->hydrate === null) {
			return $data;
		}
		$objs = [];
		$hydrateClass = $this->hydrate;
		if ($this->limit === 1) {
			$obj = $hydrateClass::hydrate($data);
			return $obj;
		}
		foreach ($data as $iData) {
			$obj = $hydrateClass::hydrate($iData);
			$objs[] = $obj;
		}
		return $objs;
	}


	protected static function encodeData($data) {
		foreach ($data as $key => $value) {
			if (is_object($value) && get_class($value) === 'ArrayObject') {
				$data[$key] = (array) $value;
			} elseif (is_object($value) && (get_class($value) === 'DateTime' || get_class($value) === 'Carbon\Carbon')) {
				$data[$key] = new \MongoDB\BSON\UTCDateTime($value);
			}
		}
		return $data;
	}

	public function insert($data) {
		$data = static::encodeData($data);
		$success = $this->connection->{$this->table}->insertOne($data);
		return $success;
	}

	public function lastInsertId() {
		return $this->lastInsertId;
	}

	public function update($data, $multiple = false) {
		$data = static::encodeData($data);
		var_dump($data); exit;
		if ($this->conditions !== []) {
			if (!$multiple) {
				return $this->connection->{$this->table}->updateOne($this->conditions, ['$set' => $data]);
			} else {
				return $this->connection->{$this->table}->updateMany($this->conditions, ['$set' => $data]);
			}
		} else {
			return null;
		}
	}

	public function remove() {
		if ($this->limit === 1) {
			return $this->connection->{$this->table}->deleteOne($this->conditions);
		} else {
			return $this->connection->{$this->table}->deleteMany($this->conditions);
		}
	}

}
