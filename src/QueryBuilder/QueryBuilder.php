<?php

namespace phpDM\QueryBuilder;

class QueryBuilder
{

	protected static $type;
	protected $interface;
	protected $connection;
	protected $hydrate;
	protected $table;
	protected $select = [];
	protected $conditions;
	protected $sort = [];
	protected $limit;
	protected $skip;

	public function __construct(string $connection = null) {
		try {
			$this->interface = \phpDM\Connections\ConnectionManager::getConnection($connection);
			if (!$this->interface) {
				$this->interface = \phpDM\Connections\ConnectionManager::getConnectionByType(static::$type);
			}
			$this->connection = $this->interface->getConnection();
		} catch (\Exception $e) { }
	}

	public function setHydrate(string $class) {
		$this->hydrate = $class;
	}

	public function table(string $table) {
		$this->table = $table;
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

	protected static function encodeData($data) {
		return $data;
	}
	
}