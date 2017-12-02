<?php

namespace phpDM\QueryBuilder;

class MysqlQueryBuilder extends QueryBuilder
{

	protected static $type = 'mysql';
	protected $table;
	protected $conditions = [];
	protected $params = [];
	protected $statement;

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
		$direction = strtoupper($direction);
		if ($direction === 'ASC' || $direction === 'DESC') {
			$this->sort[] = $field . ' ' . $direction;
		}
		return $this;
	}

	public function rowCount() {
		return $this->statement->rowCount();
	}

	public function buildSelectQuery() {
		$query = 'SELECT ';
		if (count($this->select)) {
			$query .= implode(', ', $this->select);
		} else {
			$query .= '*';
		}
		$query  .= ' FROM ' . $this->table;
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

	protected static function encodeData($data) {
		foreach ($data as $key => $value) {
/*			if (is_bool($value)) {
				$data[$key] = $value ? '1' : '0';
			} else*/if (is_object($value) && (get_class($value) === 'DateTime' || get_class($value) === 'Carbon\Carbon')) {
				$data[$key] = $value->format('Y-m-d H:i:s');
			} elseif (is_array($value) || (is_object($value) && get_class($value) === 'ArrayObject')) {
				$data[$key] = json_encode((array) $value);
			}
		}
		return $data;
	}

	public function insert($data) {
		$data = static::encodeData($data);
		$query = "INSERT INTO {$this->table} SET ";
		$columns = [];
		$values = [];
		foreach ($data as $key => $value) {
			$columns[] = "`{$key}` = :{$key}";
			$values[":{$key}"] = $value;
		}
		$query .= implode(', ', $columns);
		$this->statement = $this->connection->prepare($query);
		return $this->statement->execute($values);
	}

	public function lastInsertId() {
		return $this->connection->lastInsertId();
	}

	public function update($data, $multiple = false) {
		if ($this->conditions !== []) {
			$data = static::encodeData($data);
			$query = "UPDATE {$this->table} SET ";
			$columns = [];
			$values = [];
			foreach ($data as $key => $value) {
				$columns[] = "`{$key}` = ?";
				$values[] = $value;
			}
			$query .= implode(', ', $columns);
			if (count($this->conditions)) {
				$query .= ' WHERE ' . implode(' AND ', $this->conditions);
			}
			if ($this->limit) {
				if ($this->skip) {
					$query .= ' LIMIT ' . $this->skip . ', ' . $this->limit;
				} else {
					$query .= ' LIMIT ' . $this->limit;
				}
			}
			$this->statement = $this->connection->prepare($query);
			return $this->statement->execute(array_merge($values, $this->params));
		} else {
			return null;
		}
	}

}