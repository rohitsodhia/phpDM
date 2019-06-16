<?php

namespace phpDM\Models\Fields;

abstract class BaseField implements \JsonSerializable
{

	protected const TYPE = null;
	protected $value;
	protected $history = [];
	protected $immutable = false;

	public function __construct($value = null, bool $immutable = false) {
		$this->set($value);
		$this->immutable = $immutable;
	}

	public function get($raw = true) {
		return $this->value;
	}

	public function __sleep() {
		return ['value'];
	}

	public function set($value) {
		if (!$this->immutable) {
			$this->value = $value;
			$this->history[] = $value;
		}
		return $this->value;
	}

	public function reset($value = null) {
		if ($this->immutable) {
			throw \Exception('Cannot change an immutable value');
		}
		$this->history = [];
		$this->set($value);
	}

	public function __wakeup() {
		var_dump($this);
	}

	/**
	 * Allows object to be JSON serializable
	 *
	 * @return array Array of data values
	 */
	public function jsonSerialize()
	{
		return $this->value;
	}

	public function changed() {
		return count($this->history) > 1;
	}

	public function getChanged($raw = true) {
		return $this->get($raw);
	}

}