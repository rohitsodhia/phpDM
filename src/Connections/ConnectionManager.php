<?php

namespace phpDM\Connections;

class ConnectionManager
{

	private static $connections = [];
	private static $connectionNameMap = [];

	public static function addConnection($config, string $name = null) {
		if (sizeof(ConnectionFactory::getConnectionInterfaces()) === 0) {
			ConnectionFactory::init();
		}
		if (!$name) {
			$interface = ConnectionFactory::getConnectionInterface($config['type']);
			self::$connections[$config['type']][] = (new $interface())->createConnection($config);
		} else {
			$interface = ConnectionFactory::getConnectionInterface($config['type']);
			self::$connections[$config['type']][$name] = (new $interface())->createConnection($config);
			self::$connectionNameMap[$name] = $config['type'];
		}
	}

	public static function getConnection(string $name = null) {
		if ($name !== null) {
			return self::$connections[self::$connectionNameMap[$name]][$name];
		} else {
			return null;
		}
	}

	public static function getConnectionByType(string $type) {
		return array_values(self::$connections[$type])[0];
	}

}