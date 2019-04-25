<?php

namespace phpDM\Connections\Adapters;

/**
 * Connection adapter for MySQL
 * @package phpDM\Connections\Adapters
 */
class MysqlConnectionAdapter extends ConnectionAdapterInterface
{

	/**
	 * Creates a MySQL PDO connection
	 * @param array $config Connection configs
	 * @return mixed
	 */
	protected function createConnection(array $config = []) {
		$hostname = $config['hostname'];
		$database = $config['database'];
		unset($config['hostname'], $config['database']);
		$options = array_merge([
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
		], $config);
		$mysql = new \PDO("mysql:host={$hostname};dbname={$database}", $config['username'], $config['password'], $options);
		return $mysql;
	}

}