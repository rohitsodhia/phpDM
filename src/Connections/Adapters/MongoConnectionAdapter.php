<?php

namespace phpDM\Connections\Adapters;

/**
 * Connection adapter for MongoDB
 * @package phpDM\Connections\Adapters
 */
class MongoConnectionAdapter extends ConnectionInterface
{

	/**
	 * Creates a MySQL PDO connection
	 * @param array $config Connection configs
	 * @return mixed
	 */
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