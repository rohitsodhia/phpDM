<?php

namespace phpDM\Models\FieldFactories;

use \phpDM\Models\Fields\Mysql as Fields;

class MysqlFieldFactory extends FieldFactory {

	static public function getField(string $field) {
		if ($field === 'bool' || $field === 'boolean') {
			return Fields\BooleanField::class;
		} elseif ($field === 'int' || $field === 'integer') {
			return Fields\IntegerField::class;
		} elseif ($field === 'float') {
			return Fields\FloatField::class;
		} elseif ($field === 'str' || $field === 'string') {
			return Fields\StringField::class;
		} elseif (in_array($field, ['timestamp', 'datetime', 'createdTimestamp', 'updatedTimestamp', 'deletedTimestamp'])) {
			return Fields\DateTimeField::class;
		}
	}

}