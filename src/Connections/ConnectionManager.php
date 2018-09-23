<?php

namespace phpDM\Connections;

use phpDM\Connections\Adapters\ConnectionInterface;

class ConnectionManager
{

	/**
	 * @var array List of connections
	 */
	private static $connections = [];

	/**
	 * @var array Map of connection names to type
	 */
	private static $connectionNameTypeMap = [];

	/**
	 * @param string $type Connection type
	 * @param $config array Array containing connection details
	 * @param string|null $name Optional name for multiple connections of the same type
	 */
	public static function addConnection(string $type, $config, string $name = null) {
		if (count(ConnectionFactory::getConnectionAdapters()) === 0) {
			ConnectionFactory::init();
		}
		try {
			$adapter = ConnectionFactory::getConnectionAdapter($type);
		} catch (\Exception $e) {
			return;
		}
		$adapter = new $adapter($config);
		if (!$name) {
			self::$connections[$type][] = $adapter;
		} else {
			self::$connections[$type][$name] = $adapter;
			self::$connectionNameTypeMap[$name] = $type;
		}
	}

	/**
	 * @param string $type Connection type
	 * @param string|null $name Connection name
	 * @return ConnectionInterface|null
	 */
	public static function getConnection(string $type, string $name = null) {
		if ($name !== null && strlen($name)) {
			return key_exists($name, self::$connectionNameTypeMap) ? self::$connections[self::$connectionNameTypeMap[$name]][$name] : null;
		} elseif (key_exists($type, self::$connections)) {
			return array_values(self::$connections[$type])[0];
		}
		return null;
	}

}