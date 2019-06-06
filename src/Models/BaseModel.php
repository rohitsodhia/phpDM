<?php

namespace phpDM\Models;

use phpDM\QueryBuilder\QueryBuilder;
use phpDM\Models\Fields\BaseField;

abstract class BaseModel implements \JsonSerializable
{

	protected $_settingUp = true;

	/**
	 * @var string Model type
	 */
	protected static $type = '';

	/**
	 * @var string Specificy a connection by name
	*/
	protected static $connection = null;

	/**
	 * @var string (Optional) Set a table name
	 */
	protected static $table = null;

	/**
	 * @var array Array of pointers to special fields (primary key, created timestamp, etc)
	 */
	protected $_specialFields = [];

	protected $_defaultPrimaryKey = null;

	/**
	 * @var boolean Tracks if the model is new (not from database)
	 */
	protected $_new = true;

	/**
	 * @var boolean Marks as hydrating, bypassing some validation
	 */
	protected $_hydrating = true;

	/**
	 * @var string Format for the timestring built by Carbon
	 */
	protected $_timestampFormat = 'Y-m-d H:i:s';

	protected $_fieldFactory = null;
	protected $_data = [];

	protected $_serialized = [];

	/**
	 * Initilize model with data
	 *
	 * @param array|string $data
	 */
	public function __construct($data = null) {
		if (is_string($data) && $parsedData = json_decode($data)) {
			$data = (array) $parsedData;
		} elseif (!is_array($data)) {
			$data = [];
		}
		$this->_setupFields($data);
		$this->_hydrating = false;
	}

	protected function _setupFields($values = []) {
		foreach (get_object_vars($this) as $prop => $defaultValue) {
			if ($prop[0] !== '_') {
				$rProp = new \ReflectionProperty($this, $prop);
				$comment = $rProp->getDocComment();
				$tags = preg_match_all('/\*\s+@(\w+?)(?: (.+?))?\s/', $comment, $matches, PREG_SET_ORDER);
				$options = ['default' => $defaultValue];
				foreach ($matches as $match) {
					if (sizeof($match) == 2) {
						$match[] = null;
					}
					list($full, $tag, $tagValue) = $match;
					switch ($tag) {
						case 'type':
							$options['type'] = $tagValue;
							$options['field'] = $this->_fieldFactory::getField($tagValue);
							break;
						case 'default':
							$options['default'] = $tagValue;
							break;
						default:
							$options[$tag] = true;
							break;
					}
				}
				if (key_exists('field', $options)) {
					if (key_exists($prop, $values)) {
						$options['default'] = $values[$prop];
					}
					$this->_data[$prop] = new $options['field']($options['default']);
					switch ($options['type']) {
						case 'createdTimestamp':
						case 'updatedTimestamp':
						case 'deletedTimestamp':
							$this->_specialFields[$options['type']] = &$this->_data[$prop];
					}
					if (isset($options['primaryKey']) && $options['primaryKey']) {
						$this->_specialFields['primaryKey'] = &$this->_data[$prop];
					}
					unset($this->$prop);
				}
			}
		}
	}

	/**
	 * Allows object to be serializable
	 *
	 * @return array
	 */
	public function __sleep() {
		foreach ($this->_data as $key => $value) {
			$this->_serialized[$key] = $value->get();
		}
		return ['_serialized'];
	}

	/**
	 * Allows object to be unserializable
	 */
	public function __wakeup() {
		$this->_original = $this->_data;
	}

	/**
	 * Allows object to be JSON serializable
	 *
	 * @return array Array of data values
	 */
	public function jsonSerialize() {
		return $this->getData(true);
	}

	public static function fromJSON($data) {
		$obj = new static($data);
		return $obj;
	}

	/**
	 * If a table name is provided 
	 *
	 * @return string
	 */
	public function getTableName() {
		if (is_string(static::$table)) {
			return static::$table;
		}

		$connectionManager = \phpDM\Connections\ConnectionManager::getInstance();
		$adapter = $connectionManager->getConnection(static::$type, static::$connection, true);
		$case = $adapter->getOption('case');

		$calledClassParts = explode('\\', get_called_class());
		$table = @end($calledClassParts);
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
		$queryBuilder = $connectionFactory->getQueryBuilder(static::$type);
		$connectionManager = \phpDM\Connections\ConnectionManager::getInstance();
		$adapter = $connectionManager->getConnection(static::$type, static::$connection);
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
			$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys($this->_fields));
			$queryBuilder = $this->addSoftDeleteWhere($queryBuilder);
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
		if (!property_exists($this->_data, $key)) {
			trigger_error('Invalid field: ' . $key);
		}
		$value = $this->_data[$key]->get();
		$accessor = 'get' . \phpDM\Helpers::toCamelCase($key, true);
		if (method_exists($this, $accessor)) {
			$value = $this->{$accessor}($value);
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
		if (preg_match('/[a-z]/i', $key[0]) === 0) {
			trigger_error('Invalid field: ' . $key);
		} elseif (property_exists($this, $key) && $value instanceof BaseField) {
			$this->$key = $value;
			return;
		} elseif (!array_key_exists($key, $this->_data)) {
			trigger_error('Invalid field: ' . $key);
		}
		$mutator = 'set' . \phpDM\Helpers::toCamelCase($key, true);
		if (method_exists($this, $mutator)) {
			$this->{$mutator}($value);
			$value = $this->_data[$key];
		}
		$this->_data[$key]->set($value);
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
		$this->_new = $new;
	}

	/**
	 * Store current data as original
	 */
	public function setOriginal() {
		foreach ($this->_data as $key => $value) {
			if (is_object($value)) {
				$value = static::clone($value);
			}
			$this->_original[$key] = $value;
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
			return isset($this->_original[$field]) ? $this->_original[$field] : null;
		}
		return $this->_original;
	}

	public function resetChanged() {
		$this->_changed = [];
	}

	/**
	 * Set hydrating state
	 *
	 * @param boolean $state
	 */
	public function setHydrating(bool $state) {
		$this->_hydrating = $state;
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

	public function getData(bool $changed = false, bool $raw = false) {
		$data = [];
		foreach ($this->_data as $prop => $field) {
			if ($changed && $field->changed()) {
				$data[$field] = $this->getChanged($raw);
			} elseif (!$changed) {
				$data[$prop] = $field->get($raw);
			}
		}
		return $data;
	}

	public function getRawData(bool $changed = false) {
		return $this->getData($changed, true);
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

	protected function addTimestamps(\Carbon\Carbon $timestamp = null) {
		if (isset($this->_specialFields['createdTimestamp'])) {
			$this->_specialFields['createdTimestamp']->set($timestamp ?: new \Carbon\Carbon());
		}
		if (isset($this->_specialFields['updatedTimestamp'])) {
			$this->_specialFields[ 'updatedTimestamp']->set($timestamp ?: new \Carbon\Carbon());
		}
	}

	abstract protected function addSoftDeleteWhere(QueryBuilder $queryBuilder);

	/**
	 * Remove entry from database
	 *
	 * @return number|null
	 */
	public function remove() {
		$queryBuilder = static::getQueryBuilder();
		$return = $queryBuilder
			->table(static::getTableName())
			->softDelete(array_search('deletedTimestamp', $this->_fields))
			->where(static::$_primaryKey, $this->{static::$_primaryKey})
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
		$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys($this->_fields));
		return $queryBuilder;
	}

}
