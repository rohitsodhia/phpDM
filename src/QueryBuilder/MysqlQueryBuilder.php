<?php

namespace phpDM\QueryBuilder;

class MysqlQueryBuilder extends QueryBuilder
{

	protected static $type = 'mysql';
	protected $table;
	protected $conditions = [];
	protected $params = [];

	public function getParams() {
		return $this->params;
	}

	protected static function buildCondition($val1, $val2, $comparitor = '=') {
		// $paramKey = \phpDM\Helpers::randStr();
		if ($comparitor == '==') {
			$comparitor = '=';
		}
		if (in_array($comparitor, ['=', '>', '>=', '<', '<=', '<>', '!='])) {
			return "{$val1} {$comparitor} ?";
		}
	}
	
	public function where() {
		$args = func_get_args();
		if (is_callable($args[0])) {
			$queryBuilder = static::class;
			$subquery = new $queryBuilder();
			$args[0]($subquery);
			$conditions = '(' . $subquery->getQuery() . ')';
			$params = $subquery->getParams();
		} elseif (count($args) === 2 || (count($args) === 3 && ($args[1] === '=' || $args[1] === '=='))) {
			$conditions = self::buildCondition($args[0], end($args));
			$params = [end($args)];
		} elseif (count($args) === 3) {
			$conditions = self::buildCondition($args[0], $args[2], $args[1]);
			$params = [$args[2]];
		}
		if ($conditions) {
			$this->params = array_merge($this->params, $params);
			$this->conditions[] = $conditions;
		}
		return $this;
	}

	public function orWhere() {
		$args = func_get_args();
		if (is_callable($args[0])) {
			$queryBuilder = static::class;
			$subquery = new $queryBuilder();
			$args[0]($subquery);
			$conditions = $subquery->getQuery();
			$params = $subquery->getParams();
		} elseif (count($args) === 2 || (count($args) === 3 && ($args[1] === '=' || $args[1] === '=='))) {
			$conditions = self::buildCondition($args[0], end($args));
			$params = [end($args)];
		} elseif (count($args) === 3) {
			$conditions = self::buildCondition($args[0], $args[2], $args[1]);
			$params = [$args[2]];
		}
		if ($conditions) {
			$this->params = array_merge($this->params, $params);
			$this->conditions[count($this->conditions) - 1] = $this->conditions[count($this->conditions) - 1] . ' OR ' . $conditions;
		}
		return $this;
	}

	public function whereIn(string $field, array $values) {
		$this->conditions[] = "{$field} IN (" . implode(', ', array_fill(0, count($values), '?')) . ')';
		$this->params = array_merge($this->params, $values);
		return $this;
	}

	public function sort($field, $direction = 'asc') {
		if ($direction === 'asc' || $direction === 'desc') {
			$this->sort[] = $field . ' ' . strtoupper($direction);
		}
		return $this;
	}

	public function buildSelectQuery() {
		$query = 'SELECT ';
		if (count($this->select)) {
			$query .= implode(', ', $this->select);
		} else {
			$query .= '*';
		}
		$query  .= ' FROM ' . $this->from;
		if (count($this->conditions)) {
			$query .= ' WHERE ' . implode(' AND ', $this->conditions);
		}
		if (sizeof($this->sort)) {
			$query .= ' ORDER BY ' . implode(', ', $this->sort);
		}
		if ($this->limit) {
			if ($this->skip) {
				$query .= ' LIMIT ' . $this->skip . ', ' . $this->limit;
			} else {
				$query .= ' LIMIT ' . $this->limit;
			}
		}
		return $query;
	}

	public function first() {
		$this->limit = 1;
		$query = $this->buildSelectQuery();
		$pQuery = $this->connection->prepare($query);
		$pQuery->execute($this->params);
		$data = $pQuery->fetch();
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
		$query = $this->buildSelectQuery();
		$pQuery = $this->connection->prepare($query);
		$pQuery->execute($this->params);
		$data = $pQuery->fetchAll();
		if ($this->hydrate === null) {
			return $data;
		}
		$objs = [];
		$hydrateClass = $this->hydrate;
		foreach ($data as $iData) {
			$obj = $hydrateClass::hydrate($iData);
			$objs[] = $obj;
		}
		return $objs;
	}

	public function insert($data) {
		return $this->statement->insertOne($data);
	}

	public function update($data, $multiple = false) {
		if ($this->conditions !== []) {
			if (!$multiple) {
				return $this->statement->updateOne($this->conditions, ['$set' => $data]);
			} else {
				return $this->statement->updateMany($this->conditions, ['$set' => $data]);
			}
		} else {
			return null;
		}
	}

}