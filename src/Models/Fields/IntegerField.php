<?php

namespace phpDM\Models\Fields;

class IntegerField extends BaseField
{

	protected const TYPE = 'integer';

	public function set($value)
	{
		$value = (int) $value;
		return parent::set($value);
	}

}