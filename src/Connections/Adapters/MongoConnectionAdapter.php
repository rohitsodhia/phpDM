<?php

namespace phpDM\Connections\Adapters;

/**
 * Connection adapter for MongoDB
 * @package phpDM\Connections\Adapters
 */
class MongoConnectionAdapter extends ConnectionAdapterInterface
{

	/**
	 * Creates a MongoDB connection
	 * @param array $config Connection configs
	 * @return \MongoDB\Client
	 */
	protected function createConnection(array $config = []) {
		$config = array_merge([
		], $config);
		return (new \MongoDB\Client(
			null,
			[],
			['typeMap' => ['array' => 'array', 'document' => 'object', 'root' => 'object']]
		))->{$config['database']};
	}

}