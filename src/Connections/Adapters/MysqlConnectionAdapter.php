<?php

namespace phpDM\Connections\Adapters;

class MysqlConnectionAdapter extends ConnectionInterface
{
	
	public static function createConnection(array $config = []) {
		$config = array_merge([
			
		], $config);
		$mysql = new \PDO("mysql:host={$config['hostname']};dbname={$config['database']}", $config['username'], $config['password']);
		$mysql->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
		$mysql->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		return $mysql;
	}

}