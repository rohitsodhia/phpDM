<?php

namespace phpDM\Connections\Interfaces;

class MongoConnectionAdapter extends ConnectionInterface
{

	public static function createConnection(array $config = []) {
		$config = array_merge([
			
		], $config);
		return (new \MongoDB\Client(
			null,
			[],
			['typeMap' => ['array' => 'array', 'document' => 'object', 'root' => 'object']]
		))->{$config['database']};
	}

}