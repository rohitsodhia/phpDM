<?php

namespace phpDM\Connections;

class ConnectionManager
{

	private static $connections = [];
	private static $connectionNameTypeMap = [];

	public static function addConnection($config, string $name = null) {
		if (count(ConnectionFactory::getConnectionInterfaces()) === 0) {
			ConnectionFactory::init();
		}
		$interface = ConnectionFactory::getConnectionInterface($config['type']);
		$interface = new $interface($config);
		if (!$name) {
			self::$connections[$config['type']][] = $interface;
		} else {
			self::$connections[$config['type']][$name] = $interface;
			self::$connectionNameTypeMap[$name] = $config['type'];
		}
	}

	public static function getConnection(string $name = null, string $type = null) {
		if ($name !== null) {
			return self::$connections[self::$connectionNameTypeMap[$name]][$name];
		} elseif ($type !== null) {
			return self::getConnectionByType($type);
		} else {
			return null;
		}
	}

	public static function getConnectionByType(string $type) {
		return array_values(self::$connections[$type])[0];
	}

}