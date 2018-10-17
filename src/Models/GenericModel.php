<?php

namespace phpDM\Models;

class GenericModel
{

	protected $parent;
	protected $fields = [];
	protected $data = [];
	protected $original = [];
	protected $changed = [];

	public function __construct(string $parent = null, array $fields = null) {
		if (strlen($this->parent) && count($this->fields)) {
			return;
		}
		if (!strlen($parent) || !count($fields)) {
			throw new \Exception('Undefined GenericModel');
		}
		$this->parent = $parent;
		$this->fields = $fields;
	}

	public function __get(string $key) {
		if (!array_key_exists($key, $this->fields)) {
			trigger_error('Invalid field: ' . $key);
			return null;
		}
		$value = null;
		if (isset($this->data[$key])) {
			$value = $this->data[$key];
		} elseif ($this->fields[$key] === 'object'  || substr($this->fields[$key], 0, 7) === 'object:') {
			if ($this->fields[$key] === 'object') {
				$value = new GenericModel($this->parent, $this->fields[$key]);
			} else {
				$class = substr($this->fields[$key], 7);
				if (!class_exists($class)) {
					throw new Exception('Field not an object');
				}
				$value = new $class();
			}
			$this->data[$key] = $value;
		}
		return $value ?: null;
	}

	public function __set(string $key, $value) {
		if (!array_key_exists($key, $this->fields)) {
			trigger_error('Invalid field: ' . $key);
			return null;
		}
		$value = $this->parent::parseValue($value, $this->fields[$key]);
		$this->data[$key] = $value;
		if (!in_array($value, $this->changed)) {
			$this->changed[] = $key;
		}
	}

	public function setOriginal() {
		foreach ($this->data as $key => $value) {
			if (is_object($value)) {
				$value = clone $value;
			}
			$this->original[$key] = $value;
		}
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

	public static function hydrate(array $data, string $parent = null, array $options = null) {
		$class = static::class;
		if ($class === 'phpDM\Models\GenericModel') {
			$obj = new $class($parent, $options);
		} else {
			$obj = new $class();
		}
		foreach ($data as $key => $value) {
			$obj->$key = $value;
		}
		$obj->setOriginal();
		$obj->resetChanged();
		return $obj;
	}

	public function getData() {
		$data = [];
		foreach ($this->fields as $field => $options) {
			if (!isset($this->data[$field])) {
				continue;
			}
			$cast = $this->parent::getCast($options);
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
		$partsCast = $this->parent::getCast($cast[1]);
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
		foreach ($this->fields as $field => $options) {
			if (!isset($this->data[$field])) {
				continue;
			}
			$cast = $this->parent::getCast($options);
			if (is_array($cast) && $cast[0] === 'array' && is_object($this->data[$field]) && get_class($this->data[$field]) === 'ArrayObject') {
				$original = $this->getOriginal($field);
				if (json_encode($this->data[$field]) !== json_encode($original)) {
					$changedData[$field] = $this->data[$field];
				}
			} elseif (is_string($cast)) {
				if (in_array($field, $this->changed)) {
					$changedData[$field] = $this->data[$field];
				}
			} else {
				if (in_array($field, $this->changed) && !is_object($this->data[$field])) {
					$changedData[$field] = null;
				} elseif (!in_array($field, $this->changed) && is_object($this->data[$field])) {
					// $data = $this->data[$field]->getChangedFields();
					$data = $this->data[$field]->getData();
					if (count($data) && $data !== $this->getOriginal($field)->getData()) {
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

}