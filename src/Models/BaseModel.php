<?php

namespace phpDM\Models;

class BaseModel implements \JsonSerializable
{

	public static $type;
	public static $connection;
	protected $table;
	protected $new = true;
	static protected $primaryKey;
	static protected $timestampFormat = 'Y-m-d H:i:s';
	static protected $fields = [];
	protected $data = [];
	protected $original = [];
	protected $changed = [];

	public function __construct() {
	}

	public function jsonSerialize() {
		return $this->getFields(true);
	}

	protected static function getTableName() {
		if (isset(static::$table)) {
			return static::$table;
		}

		$table = @end(explode('\\', get_called_class()));
		$table = \phpDM\Inflect::pluralize($table);
		$connection = \phpDM\Connections\ConnectionManager::getConnection(static::$connection, static::$type);
		$case = $connection->getOption('case');
		if ($case === 'camel') {
			$table = lcfirst($table);
		} else {
			$table = \phpDM\Helpers::toSnakeCase($table);
		}
		return $table;
	}

	public static function __callStatic($method, $params) {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		if (method_exists($queryBuilder, $method)) {
			$queryBuilder = new $queryBuilder(static::$connection ?: null);
			$queryBuilder->setHydrate(static::class);
			$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys(static::$fields));
			$queryBuilder = static::addSoftDeleteWhere($queryBuilder);
			return call_user_func_array([$queryBuilder, $method], $params);
		}
	}

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
		} elseif ($this->fields[$key] === 'object'  || substr($this->fields[$key], 0, 7) === 'object:') {
			if ($this->fields[$key] === 'object') {
				$value = new GenericModel(static::class, $this->fields[$key]['fields']);
			} else {
				$class = substr($this->fields[$key], 7);
				if (!class_exists($class)) {
					throw new Exception('Field not an object');
				}
				$value = new $class();
			}
			$this->data[$key] = $value;
		}
		return $value;
	}

	public function __set(string $key, $value) {
		if (!array_key_exists($key, static::$fields)) {
			trigger_error('Invalid field: ' . $key);
			return null;
			// throw new \Exception('Invalid field: ' . $key);
		}
		$accessor = 'set' . \phpDM\Helpers::toCamelCase($key, true);
		if (method_exists($this, $accessor)) {
			$value = $this->{$accessor}($value);
		}
		$value = static::parseValue($value, static::$fields[$key]);
		$this->data[$key] = $value;
		if (!in_array($value, $this->changed)) {
			$this->changed[] = $key;
		}
	}

	protected static function getCast($cast) {
		if (gettype($cast) === 'string') {
			return $cast;
		} elseif (isset($cast['type']) && gettype($cast['type']) === 'string') {
			return $cast['type'];
		}
		return null;
	}

	public static function parseValue($value, $options) {
		$cast = static::getCast($options);
		
		if ($castValue = static::castValue($cast, $value)) {
			return $castValue;
		} elseif (preg_match('/array\((.+?)\)/', $cast, $match)) {
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
			$casts = preg_split('/\W+/', $match[1]);
			if (count($casts) === 0) {
				throw new Exception('Invalid cast');
			}
			foreach ($value as $key => $sValue) {
				$value[$key] = static::castValue($casts[0], $sValue);
			}
			return $value;
		} elseif ($cast === 'object' || substr($cast, 0, 7) === 'object:') {
			if ($cast === 'object') {
				$cleanObj = GenericModel::hydrate(static::class, $options['fields'], (array) $value);
			} else {
				$class = substr($cast, 7);
				// if (!isset($options['type'])) {
				// 	throw new \Exception('No type defined: ' . $key);
				// }
				$cleanObj = $class::hydrate((array) $value);
			}
			return $cleanObj;
		}

		return $value;
	}

	public static function castValue(string $cast, $value) {
		if ($cast === 'bool' || $cast === 'boolean') {
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
			} elseif (in_array(gettype($value), ['integer', 'string'])) {
				return new \Carbon\Carbon($value);
			}
		}
	}

	public function setNew(bool $new) {
		$this->new = $new;
	}

	public function setOriginal() {
		foreach ($this->data as $key => $value) {
			if (is_object($value)) {
				$value = static::clone($value);
			}
			$this->original[$key] = $value;
		}
	}

	protected static function clone($value) {
		return clone $value;
	}

	public function getOriginal(string $field = null) {
		if ($field) {
			return isset($this->original[$field]) ? $this->original[$field] : null;
		}
		return $this->original;
	}

	public function resetChanged() {
		$this->changed = [];
	}

	public static function hydrate($data) {
		if ($data === null) {
			return null;
		}
		$class = static::class;
		$obj = new $class();
		if (count($data)) {
			foreach ($data as $key => $value) {
				$obj->$key = $value;
			}
		}
		$obj->setOriginal();
		$obj->resetChanged();
		$obj->setNew(false);
		return $obj;
	}

	public function getFields($pure = false) {
		$data = [];
		foreach (static::$fields as $field => $options) {
			if (!isset($this->data[$field])) {
				continue;
			}
			$cast = static::getCast($options);
			if (substr($cast, 0, 6) !== 'object') {
				$data[$field] = $this->data[$field];
			} else {
				if (is_object($this->data[$field])) {
					$cData = $this->data[$field]->getFields();
					if (count($cData)) {
						$data[$field] = $cData;
					}
				}
			}
			if ($pure && is_object($data[$field]) && get_class($data[$field]) === 'ArrayObject') {
				$data[$field] = (array) $data[$field];
			}
		}
		return $data;
	}

	public function getChangedFields($pure = false) {
		$changedData = [];
		foreach (static::$fields as $field => $options) {
			if (!isset($this->data[$field])) {
				continue;
			}
			$cast = static::getCast($options);
			if (substr($cast, 0, 5) === 'array' && is_object($this->data[$field]) && get_class($this->data[$field]) === 'ArrayObject') {
				$original = $this->getOriginal($field);
				if (json_encode($this->data[$field]) !== json_encode($original)) {
					$changedData[$field] = $this->data[$field];
				}
			} elseif (substr($cast, 0, 6) !== 'object') {
				if (in_array($field, $this->changed)) {
					$changedData[$field] = $this->data[$field];
				}
			} else {
				if (in_array($field, $this->changed) && !is_object($this->data[$field])) {
					$changedData[$field] = null;
				} elseif (!in_array($field, $this->changed) && is_object($this->data[$field])) {
					// $data = $this->data[$field]->getChangedFields();
					$data = $this->data[$field]->getFields();
					if (count($data) && $data !== $this->getOriginal($field)->getFields()) {
						$changedData[$field] = $data;
					}
				}
			}
			if ($pure && is_object($changedData[$field]) && get_class($changedData[$field]) === 'ArrayObject') {
				$changedData[$field] = (array) $changedData[$field];
			}
		}
		return $changedData;
	}

	protected function addTimestamps(\Carbon\Carbon $timestamp = null) {
		if (($key = array_search('createdTimestamp', static::$fields)) !== false) {
			if ($this->data[$key] === null) {
				$this->data[$key] = $timestamp ?: new \Carbon\Carbon();
			}
		}
		if (($key = array_search('updatedTimestamp', static::$fields)) !== false) {
			$this->data[$key] = $timestamp ?: new \Carbon\Carbon();
		}
	}

	public function remove() {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		$queryBuilder = new $queryBuilder(static::$connection ?: null);
		$return = $queryBuilder
			->table(static::getTableName())
			->softDelete(array_search('deletedTimestamp', static::$fields))
			->where(static::$primaryKey, $this->{static::$primaryKey})
			->limit(1)
			->delete();
		return $return ? $queryBuilder->rowCount() : null;
	}

	public static function query() {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		$queryBuilder->setHydrate(static::class);
		$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys(static::$fields));
		return $queryBuilder;
	}

}
