<?php

namespace phpDM\Models\FieldFactories;

abstract class FieldFactory {

	abstract static public function getField(string $field);

}