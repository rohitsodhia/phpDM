<?php

namespace phpDM\Models\Fields;

class FloatField extends BaseField
{

	protected const TYPE = 'float';

	public function set($value)
	{
		$value = (float) $value;
		return parent::set($value);
	}

}