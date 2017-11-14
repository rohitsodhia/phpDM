<?php

namespace phpDM\Models;

class BaseModel
{

	public static $type;
	public $connection;
	protected $table;
	protected $new = true;
	static protected $primaryKey;
	static protected $fields = [];
	protected $data = [];
	protected $changed = [];

	public function __construct() {
	}

	protected static function getTableName() {
		if (isset(static::$table)) {
			return static::$table;
		}
		
		$table = @end(explode('\\', get_called_class()));
		$table = \phpDM\Inflect::pluralize($table);
		$table = \phpDM\Helpers::toSnakeCase($table);
		return $table;
	}

	public static function __callStatic($method, $params) {
		$queryBuilder = \phpDM\Connections\ConnectionFactory::getQueryBuilder(static::$type);
		if (method_exists($queryBuilder, $method)) {
			$queryBuilder = new $queryBuilder();
			$queryBuilder->setHydrate(static::class);
			$queryBuilder = $queryBuilder->table(static::getTableName())->select(array_keys(static::$fields));
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
		}
		return $value ?: null;
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
		if (gettype(static::$fields[$key]) === 'string') {
			$value = static::castValue(static::$fields[$key], $value);
		}
		$this->data[$key] = $value;
		if (!in_array($value, $this->changed)) {
			$this->changed[] = $key;
		}
	}

	protected static function castValue(string $cast, $value) {
		if ($cast === 'bool' || $cast === 'boolean') {
			return (bool) $value;
		} elseif ($cast === 'int' || $cast === 'integer') {
			return (int) $value;
		} elseif ($cast === 'float') {
			return (float) $value;
		} elseif ($cast === 'string') {
			return (string) $value;
		} elseif ($cast === 'timestamp') {
			if ($value instanceof \Carbon\Carbon) {
				return $value;
			} elseif ($value instanceof \DateTime) {
				return \Carbon\Carbon::instance($value->toDateTime());
			} elseif (in_array(gettype($value), ['integer', 'string'])) {
				return new \Carbon\Carbon($value);
			}
		} elseif (preg_match('/array\((.+?)\)/', $cast, $match)) {
			if (gettype($value) !== 'array') {
				return [];
			}
			$casts = preg_split('/\W+/', $match[1]);
			if (count($casts) === 0) {
				throw new Exception('Invalid cast');
			}
			foreach ($value as $key => $sValue) {
				$value[$key] = static::castValue($casts[0], $sValue);
			}
			return $value;
		}
	}

	public function setNew(bool $new) {
		$this->new = $new;
	}

	public function resetChanged() {
		$this->changed = [];
	}

	public static function hydrate($data) {
		$class = static::class;
		$obj = new $class();
		foreach ($data as $key => $value) {
			$obj->$key = $value;
		}
		$obj->resetChanged();
		$obj->setNew(false);;
		return $obj;
	}

}