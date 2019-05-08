<?php

namespace phpDM\Models\Fields;

class BooleanField extends BaseField
{

	protected const TYPE = 'boolean';

	public function set($value)
	{
		$value = (bool) $value;
		return parent::set($value);
	}

}