<?php

namespace phpDM\QueryBuilder;

class QueryBuilder
{

	protected static $type;
	protected $connection;
	protected $hydrate;
	protected $from;
	protected $select = [];
	protected $conditions;
	protected $sort = [];
	protected $limit;
	protected $skip;

	public function __construct(string $connection = null) {
		try {
			$this->connection = \phpDM\Connections\ConnectionManager::getConnection($connection);
			if (!$this->connection) {
				$this->connection = \phpDM\Connections\ConnectionManager::getConnectionByType(static::$type);
			}
		} catch (\Exception $e) { }
	}

	public function setHydrate(string $class) {
		$this->hydrate = $class;
	}

	public function table(string $table) {
		$this->from = $table;
		return $this;
	}

	public function getConditions() {
		return $this->conditions;
	}

	public function select($select) {
		if (gettype($select) === 'string') {
			$this->select[] = $select;
		} else {
			$this->select = array_merge($this->select, $select);
		}
		return $this;
	}
	
	public function where() {
		return $this;
	}

	public function orWhere() {
		return $this;
	}

	public function whereIn(string $field, array $values) {
		return $this;
	}

	public function sort($field, $direction = 'asc') {
		return $this;
	}

	public function limit(int $limit) {
		$this->limit = $limit;
		return $this;
	}

	public function skip(int $skip) {
		$this->skip = $skip;
		return $this;
	}

	public function paginate(int $numItems, int $page) {
		$this->limit = $numItems;
		$this->skip = ($page - 1) * $numItems;
		return $this;
	}

	public function first() {
		$options = $this->buildOptions();
		$data = $this->connection->findOne($this->conditions, $options);
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
		$data = $this->connection->find($this->conditions, $options);
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
	}

	public function update($data) {
	}

}