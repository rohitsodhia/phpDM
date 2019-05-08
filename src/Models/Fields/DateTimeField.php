<?php

namespace phpDM\Models\Fields;

class DateTimeField extends BaseField
{

	protected const TYPE = 'datetime';

	public function set($value)
	{
		if ($value instanceof \Carbon\Carbon) {
			$value = $value;
		} elseif ($value instanceof \DateTime) {
			$value = \Carbon\Carbon::instance($value);
		} elseif (gettype($value) === 'string') {
			$value = new \Carbon\Carbon($value);
		} elseif (gettype($value) === 'integer') {
			$value = \Carbon\Carbon::createFromTimestamp($value);
		}
		return parent::set($value);
	}

}