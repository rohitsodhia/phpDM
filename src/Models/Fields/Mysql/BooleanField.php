<?php

namespace phpDM\Models\Fields\Mysql;

class BooleanField extends \phpDM\Models\Fields\BooleanField
{

	public function get($raw = true) {
		if (!$raw) {
			return $this->value ? 1 : 0;
		}
		return $this->value;
	}

}