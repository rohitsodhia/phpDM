<?php

namespace phpDM\Models\Fields;

class StringField extends BaseField
{

	protected const TYPE = 'string';

	public function set($value)
	{
		if ($value !== null) {
			$value = (string) $value;
		}
		return parent::set($value);
	}

}