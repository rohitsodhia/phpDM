<?php

namespace phpDM\Models\Fields;

abstract class BaseField
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

	public function set($value) {
		$this->value = $value;
		$this->history[] = $value;
		return $this->value;
	}

}