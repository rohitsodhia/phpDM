<?php

namespace phpDM\Models;

class GenericModel
{

	private $parent;
	private $fields = [];
	private $data = [];
	private $original = [];
	private $changed = [];

	public function __construct(string $parent, array $fields) {
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

	public static function hydrate(string $parent, array $options, array $data) {
		$class = static::class;
		$obj = new $class($parent, $options);
		foreach ($data as $key => $value) {
			$obj->$key = $value;
		}
		$obj->setOriginal();
		$obj->resetChanged();
		return $obj;
	}

	public function getFields() {
		$data = [];
		foreach ($this->fields as $field => $options) {
			if (!isset($this->data[$field])) {
				continue;
			}
			$cast = $this->parent::getCast($options);
			if (substr($cast, 0, 6) !== 'object') {
				$data[$field] = $this->data[$field];
			} elseif (is_object($this->data[$field])) {
				$cData = $this->data[$field]->getFields();
				if (count($cData)) {
					$data[$field] = $cData;
				}
			}
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

}