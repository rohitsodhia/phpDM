<?php

namespace phpDM\Models\Fields;

abstract class BaseField implements \JsonSerializable
{

	protected const TYPE = null;
	protected $value;
	protected $history = [];

	public function __construct($value = null) {
		$this->set($value);
	}

	public function get() {
		return $this->value;
	}

	public function __sleep() {
		return ['value'];
	}

	public function set($value) {
		$this->value = $value;
		$this->history[] = $value;
		return $this->value;
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

}