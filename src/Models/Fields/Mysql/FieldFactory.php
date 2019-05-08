<?php

namespace phpDM\Models\Fields\Mysql;

class FieldFactory extends \phpDM\Models\Fields\FieldFactory
{

	public static function new($type)
	{
		switch ($type) {
			case 'bool':
			case 'boolean':
				return new BooleanField();
			case 'int':
			case 'integer':
				return new IntegerField();
			case 'float':
				return new FloatField();
			case 'str':
			case 'string':
				return new StringField();
			case 'timestamp':
			case 'datetime':
			case 'createdTimestamp':
			case 'updatedTimestamp':
			case 'deletedTimestamp':
				return new DateTimeField();
		}
	}
}
