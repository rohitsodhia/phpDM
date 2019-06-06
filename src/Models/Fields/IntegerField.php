<?php

namespace phpDM\Models\Fields;

class IntegerField extends BaseField
{

	protected const TYPE = 'integer';

	public function set($value)
	{
		$value = $value !== null ? (int) $value : null;
		return parent::set($value);
	}

}