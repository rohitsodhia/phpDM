<?php

namespace phpDM\QueryBuilder;

/**
 * Class QueryBuilder
 * @package phpDM\Connections
 */
abstract class QueryBuilder
{

	/**
	 * @var string Database type
	 */
	protected const TYPE = null;

	/**
	 * @var ConnectionAdapterInterface Instance of the type specific connection adapter
	 */
	protected $adapter;

	/**
	 * @var BaseModel Determines returning a raw response or hydrating an object
	 */
	protected $hydrate;

	/**
	 * @var string Database table name
	 */
	protected $table;

	/**
	 * @var array Fields to retrieve
	 */
	protected $select = [];

	/**
	 * @var array Query conditions
	 */
	protected $conditions;

	/**
	 * @var array Sort conditions
	 */
	protected $sort = [];

	/**
	 * @var int Max number of entries to return
	 */
	protected $limit;

	/**
	 * @var int How many entires to skip before retriving
	 */
	protected $skip;

	/**
	 * @var string Field to update instead of deleting, if set
	 */
	protected $softDelete;

	/**
	 * QueryBuilder constructor
	 * 
	 * @param string $connectionName
	 */
	public function __construct(string $connectionName = null)
	{
		$connectionManager = \phpDM\Connections\ConnectionManager::getInstance();
		$this->adapter = $connectionManager->getConnection(static::$type, $connectionName, true);
	}

	/**
	 * Set class to hydrate with query results
	 * 
	 * @param string $class Class name
	 */
	public function setHydrate(string $class): void
	{
		$this->hydrate = $class;
	}

	/**
	 * Set table
	 * 
	 * @param string $table Table name
	 * @return QueryBuilder Current object to chain
	 */
	public function table(string $table)
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * Returns the array format of set conditions
	 * 
	 * @return array Set conditions
	 */
	public function getConditions(): array
	{
		return $this->conditions;
	}

	/**
	 * Set fields to return from query
	 * 
	 * @param string|array $select string|array Fields to return
	 * @return QueryBuilder Current object to chain
	 */
	public function select(\mixed $select)
	{
		if (gettype($select) === 'string') {
			$this->select[] = $select;
		} else {
			$this->select = array_merge($this->select, $select);
		}
		return $this;
	}

	/**
	 * Abstract to set query conditions
	 */
	abstract public function where();

	/**
	 * Abstract to set query or conditions
	 */
	abstract public function orWhere();

	/**
	 * Abstract to set query in set conditions
	 */
	abstract public function whereIn(string $field, array $values);

	/**
	 * Abstract to set query sorting
	 */
	abstract public function sort($field, $direction = 'asc');

	/**
	 * Set query limit
	 * 
	 * @param integer $limit Max number of rows to return
	 * @return QueryBuilder Current object to chain
	 */
	public function limit(int $limit)
	{
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Set query skip
	 * 
	 * @param integer $skip Number of rows to skip before returning results
	 * @return QueryBuilder Current object to chain
	 */
	public function skip(int $skip)
	{
		$this->skip = $skip;
		return $this;
	}

	/**
	 * Shortcut method to set limit and skip
	 *
	 * @param integer $numItems Limit
	 * @param integer $page Used to calculate skip
	 * @return QueryBuilder Current object to chain
	 */
	public function paginate(int $numItems, int $page)
	{
		$this->limit($numItems);
		$this->skip(($page - 1) * $numItems);
		return $this;
	}

	/**
	 * Abstract method for triggering the retrival of data
	 */
	abstract public function get();

	/**
	 * Shortcut method to get back a single entry
	 * 
	 * @return mixed
	 */
	public function first()
	{
		$this->limit = 1;
		return $this->get();
	}

	/**
	 * Encode data to save into database
	 *
	 * @param array $data 
	 * @return void
	 */
	protected static function encodeData($data)
	{
		return $data;
	}

	/**
	 * Define a field to store a soft delete timestamp
	 *
	 * @param string $field
	 * @return QueryBuilder Current object to chain
	 */
	public function softDelete(string $field)
	{
		$this->softDelete = $field;
		return $this;
	}

	/**
	 * Delete a database entry, either permenently or by setting the soft delete
	 *
	 * @return mixed
	 */
	public function delete()
	{
		if ($this->softDelete) {
			return $this->update([$this->softDelete => new \Carbon\Carbon()]);
		} else {
			return $this->remove();
		}
	}
}
