<?php

namespace phpDM\QueryBuilder;

abstract class QueryBuilder
{

	protected static $type;
	protected $adapter;
	protected $connection;
	protected $hydrate;
	protected $table;
	protected $select = [];
	protected $conditions;
	protected $sort = [];
	protected $limit;
	protected $skip;
	protected $softDelete;

	public function __construct(?string $connection) {
		$this->adapter = \phpDM\Connections\ConnectionManager::getConnection(static::$type, $connection);
		if ($this->adapter) {
			$this->connection = $this->adapter->getConnection();
		} else {
			throw new \Exception('No connection');
		}
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

	public function first() {
		$this->limit = 1;
		return $this->get();
	}

	abstract public function get();

	protected static function encodeData($data) {
		return $data;
	}

	public function softDelete(string $field) {
		$this->softDelete = $field;
		return $this;
	}

	public function delete() {
		if ($this->softDelete) {
			$this->update([$this->softDelete => new \Carbon\Carbon()]);
		} else {
			$this->remove();
		}
	}
	
}