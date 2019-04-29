<?php

namespace phpDM\Models;

use phpDM\QueryBuilder\QueryBuilder;

abstract class BaseModel implements \JsonSerializable
{

	/**
	 * @var string Model type
	 */
	private const TYPE = '';

	/**
	 * @var string Specificy a connection by name
	*/
	public CONST CONNECTION = null;

	/**
	 * @var string (Optional) Set a table name
	 */
	protected CONST TABLE = null;

	/**
	 * @var boolean Tracks if the model is new (not from database)
	 */
	protected $new = true;

	/**
	 * @var boolean Marks as hydrating, bypassing some validation
	 */
	protected $hydrating = false;

	/**
	 * @var string Collection primary key
	 */
	static protected $primaryKey;

	/**
	 * @var string Format for the timestring built by Carbon
	 */
	static protected $timestampFormat = 'Y-m-d H:i:s';

	static protected $fields = [];
	protected $data = [];
	protected $original = [];
	protected $changed = [];

	/**
	 * Initilize model with data
	 *
	 * @param array $data
	 */
	public function __construct($data = null) {
		if ($data) {
			foreach ($data as $field => $value) {
				if (array_key_exists($field, static::$fields)) {
					$this->$field = $value;
				}
			}
			$this->original = $this->data;
			$this->resetChanged();
		}
	}

	/**
	 * Allows object to be serializable
	 *
	 * @return array
	 */
	public function __sleep() {
		return ['data'];
	}

	/**
	 * Allows object to be unserializable
	 */
	public function __wakeup() {
		$this->original = $this->data;
	}

	/**
	 * Allows method to be JSON serializable
	 *
	 * @return array Array of data values
	 */
	public function jsonSerialize() {
		return $this->getData(true);
	}

	/**
	 * If a table name is provided 
	 *
	 * @return string
	 */
	public static function getTableName() {
		if (isset(static::$table)) {
			return static::$table;
		}

		$connectionManager = \phpDM\Connections\ConnectionManager::getInstance();
		$adapter = $connectionManager->getConnection(static::TYPE, self::CONNECTION);
		if (!$adapter) {
			throw new \Exception('No connection');
		}
		$case = $adapter->getOption('case');

		$table = @end(explode('\\', get_called_class()));
		$table = \phpDM\Inflect::pluralize($table);
		if ($case === 'camel') {
			$table = lcfirst($table);
		} else {
			$table = \phpDM\Helpers::toSnakeCase($table);
		}
		return $table;
	}

	/**
	 * Undocumented function
	 *
	 * @return QueryBuilder
	 */
	public static function getQueryBuilder(): QueryBuilder {
		$connectionFactory = \phpDM\Connections\ConnectionFactory::getInstance();
		$queryBuilder = $connectionFactory->getQueryBuilder(self::TYPE);
		$connectionManager = \phpDM\Connections\ConnectionManager::getInstance();
		$adapter = $connectionManager->getConnection(static::TYPE, self::CONNECTION);
		$queryBuilder = new $queryBuilder($adapter ?: '');
		$queryBuilder->table(static::getTableName())->setHydrate(static::class);
		return $queryBuilder;
	}

	/**
	 * Allows using query builder methods as a static on a Model
	 *
	 * @param string $method Query builder method
	 * @param mixed $params Values passed to query builder method
	 * @return QueryBuilder
	 */
	public static function __callStatic($method, $params): QueryBuilder {
		$queryBuilder = static::getQueryBuilder();
		if (method_exists($queryBuilder, $method)) {
			$queryBuilder->setHydrate(static::class);
			$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys(static::$fields));
			$queryBuilder = static::addSoftDeleteWhere($queryBuilder);
			return call_user_func_array([$queryBuilder, $method], $params);
		}
	}

	/**
	 * Access fields as direct properties of the model
	 *
	 * @param string $key Field to get
	 * @return mixed
	 */
	public function __get(string $key) {
		if (!array_key_exists($key, static::$fields)) {
			trigger_error('Invalid field: ' . $key);
			return null;
		}
		$value = null;
		if (isset($this->data[$key])) {
			$value = $this->data[$key];
			$accessor = 'get' . \phpDM\Helpers::toCamelCase($key, true);
			if (method_exists($this, $accessor)) {
				$value = $this->{$accessor}($value);
			}
		} elseif (isset($this->key) && ($this->$key === 'object'  || substr($this->$key, 0, 7) === 'object:')) {
			if ($this->$key === 'object') {
				$value = new GenericModel(static::class, $this->$key['fields']);
			} else {
				$class = substr($this->$key, 7);
				if (!class_exists($class)) {
					throw new Exception('Field not an object');
				}
				$value = new $class();
			}
			$this->data[$key] = $value;
		}
		return $value;
	}

	/**
	 * Set fields as direct properties of the model
	 *
	 * @param string $key Field to set
	 * @param mixed $value Value of field
	 */
	public function __set(string $key, $value) {
		if (!array_key_exists($key, static::$fields)) {
			trigger_error('Invalid field: ' . $key);
			// throw new \Exception('Invalid field: ' . $key);
		}
		if (!$this->hydrating) {
			$accessor = 'set' . \phpDM\Helpers::toCamelCase($key, true);
			if (method_exists($this, $accessor)) {
				$this->{$accessor}($value);
				$value = $this->data[$key];
			}
		}
		$value = static::parseValue($value, static::$fields[$key]);
		$this->data[$key] = $value;
		if (!in_array($value, $this->changed)) {
			$this->changed[] = $key;
		}
	}

	protected function getCast($cast) {
		if (gettype($cast) === 'array' && isset($cast['type']) && gettype($cast['type']) === 'string') {
			$cast = $cast['type'];
		}
		if (substr($cast, 0, 5) === 'array') {
			preg_match('/array\((.+?)\)/', $cast, $match);
			$match[1] = str_replace(' ', '', $match[1]);
			$casts = preg_split('/[,]+/', $match[1]);
			if (count($casts) === 0) {
				throw new Exception('Invalid cast');
			}
			$cast = array_merge(['array'], $casts);
		} elseif ($cast === 'object') {
			$cast = ['object', 'GenericModel'];
		} elseif (substr($cast, 0, 7) === 'object:') {
			$parts = explode(':', $cast);
			$cast = ['object', $parts[1]];
		}
		return $cast;
	}

	public static function parseValue($value, $options) {
		$cast = $this->getCast($options);

		if (is_string($cast)) {
			return $this->castValue($cast, $value);
		} elseif ($cast[0] === 'array') {
			if (gettype($value) === 'string' && $decoded = json_decode($value)) {
				if (gettype($decoded) === 'array') {
					$value = new \ArrayObject($decoded);
				} else {
					return new \ArrayObject();
				}
			} elseif (gettype($value) === 'array') {
				$value = new \ArrayObject($value);
			} elseif (!is_object($value) && get_class($value) !== 'ArrayOjbect') {
				return new \ArrayObject();
			}
			foreach ($value as $key => $sValue) {
				$value[$key] = static::parseValue($sValue, $cast[1]);
			}
			return $value;
		} elseif ($cast[0] === 'object') {
			if ($cast[1] === 'GenericModel') {
				$cleanObj = GenericModel::hydrate((array) $value, static::class, $options['fields']);
			} else {
				$class = $cast[1];
				// if (!isset($options['type'])) {
				// 	throw new \Exception('No type defined: ' . $key);
				// }
				$cleanObj = $class::hydrate((array) $value);
			}
			return $cleanObj;
		}

		return $value;
	}

	protected function castValue(string $cast, $value) {
		if ($value === null) {
			return null;
		} elseif ($cast === 'bool' || $cast === 'boolean') {
			return (bool) $value;
		} elseif ($cast === 'int' || $cast === 'integer') {
			return (int) $value;
		} elseif ($cast === 'float') {
			return (float) $value;
		} elseif ($cast === 'str' || $cast === 'string') {
			return (string) $value;
		} elseif (in_array($cast, ['timestamp', 'datetime', 'createdTimestamp', 'updatedTimestamp', 'deletedTimestamp'])) {
			if ($value instanceof \Carbon\Carbon) {
				return $value;
			} elseif ($value instanceof \DateTime) {
				return \Carbon\Carbon::instance($value->toDateTime());
			} elseif (gettype($value) === 'string') {
				return new \Carbon\Carbon($value);
			} elseif (gettype($value) === 'integer') {
				return \Carbon\Carbon::createFromTimestamp($value);
			}
		}
	}

	/**
	 * Mark a model as new, meaning not in the database
	 *
	 * @param boolean $new
	 */
	public function setNew(bool $new) {
		$this->new = $new;
	}

	/**
	 * Store current data as original
	 */
	public function setOriginal() {
		foreach ($this->data as $key => $value) {
			if (is_object($value)) {
				$value = static::clone($value);
			}
			$this->original[$key] = $value;
		}
	}

	/**
	 * Wrapper to clone values
	 *
	 * @param mixed $value
	 */
	protected static function clone($value) {
		return clone $value;
	}

	/**
	 * Get original value of a field or all fields
	 *
	 * @param string $field
	 * @return mixed
	 */
	public function getOriginal(string $field = null) {
		if ($field) {
			return isset($this->original[$field]) ? $this->original[$field] : null;
		}
		return $this->original;
	}

	public function resetChanged() {
		$this->changed = [];
	}

	/**
	 * Set hydrating state
	 *
	 * @param boolean $state
	 */
	public function setHydrating(bool $state) {
		$this->hydrating = $state;
	}

	/**
	 * Populates object with supplied data, setting it as original
	 *
	 * @param array $data
	 * @return BaseModel
	 */
	public static function hydrate($data) {
		if ($data === null) {
			return null;
		}
		$class = static::class;
		$obj = new $class();
		$obj->setHydrating(true);
		if (count($data)) {
			foreach ($data as $key => $value) {
				$obj->$key = $value;
			}
		}
		$obj->setHydrating(false);
		$obj->setOriginal();
		$obj->resetChanged();
		$obj->setNew(false);
		return $obj;
	}

	public function getData() {
		$data = [];
		foreach (static::$fields as $field => $options) {
//			if (!array_key_exists($field, $this->data)) {
//				continue;
//			}
			$cast = $this->getCast($options);
			if (is_string($cast)) {
				$data[$field] = $this->data[$field];
			} elseif ($cast[0] === 'array') {
				$data[$field] = $this->getArray($cast, (array) $this->data[$field]);
			} elseif ($cast[0] === 'object') {
				if (is_object($this->data[$field])) {
					$cData = $this->data[$field]->getData();
					if (count($cData)) {
						$data[$field] = $cData;
					}
				}
			}
		}
		return $data;
	}

	protected function getArray(array $cast, array $fieldValue) {
		$partsCast = $this->getCast($cast[1]);
		if (is_string($partsCast)) {
			$data = $fieldValue;
		} elseif ($partsCast[0] === 'object') {
			$data = [];
			foreach ($fieldValue as $object) {
				$data[] = $object->getData();
			}
		} elseif ($partsCast[0] === 'array') {
			$data = $this->getArray($partsCast, $fieldValue);
		}
		return $data;
	}

	public function getChangedFields($pure = false) {
		$changedData = [];
		foreach (static::$fields as $field => $options) {
			if (!isset($this->data[$field])) {
				continue;
			}
			$cast = $this->getCast($options);
			if (is_string($cast)) {
				if (in_array($field, $this->changed)) {
					$changedData[$field] = $this->data[$field];
				}
			} elseif (is_array($cast) && $cast[0] === 'array' && is_object($this->data[$field]) && get_class($this->data[$field]) === 'ArrayObject') {
				$original = $this->getArray($cast, (array) $this->getOriginal($field));
				$current = $this->getArray($cast, (array) $this->data[$field]);
				if (json_encode($current) !== json_encode($original)) {
					$changedData[$field] = $current;
				}
			} else {
				if (in_array($field, $this->changed) && !is_object($this->data[$field])) {
					$changedData[$field] = null;
				} elseif (!in_array($field, $this->changed) && is_object($this->data[$field])) {
					$data = $this->data[$field]->getData();
					if (count($data) && $data !== $this->getOriginal($field)->getData()) {
						$changedData[$field] = $data;
					}
				}
			}
		}
		return $changedData;
	}

	protected function addTimestamps(\Carbon\Carbon $timestamp = null) {
		if (($key = array_search('createdTimestamp', static::$fields)) !== false) {
			if (!isset($this->data[$key]) || $this->data[$key] === null) {
				$this->data[$key] = $timestamp ?: new \Carbon\Carbon();
			}
		}
		if (($key = array_search('updatedTimestamp', static::$fields)) !== false) {
			$this->data[$key] = $timestamp ?: new \Carbon\Carbon();
		}
	}

	/**
	 * Remove entry from database
	 *
	 * @return number|null
	 */
	public function remove() {
		$queryBuilder = static::getQueryBuilder();
		$return = $queryBuilder
			->table(static::getTableName())
			->softDelete(array_search('deletedTimestamp', static::$fields))
			->where(static::$primaryKey, $this->{static::$primaryKey})
			->limit(1)
			->delete();
		return $return ? $queryBuilder->rowCount() : null;
	}

	/**
	 * Get empty query builder associated with model
	 *
	 * @return QueryBuilder
	 */
	public static function query() {
		$queryBuilder = static::getQueryBuilder();
		$queryBuilder->setHydrate(static::class);
		$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys(static::$fields));
		return $queryBuilder;
	}

}
