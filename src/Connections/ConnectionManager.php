<?php

namespace phpDM\Connections;

use phpDM\Connections\Adapters\ConnectionInterface;

class ConnectionManager
{

	/**
	 * @var ConnectionManager Singleton instance
	 */
	private static $instance = null;

	/**
	 * @var ConnectionFactory Singleton instance
	 */
	private $connectionFactory = null;

	/**
	 * @var array List of connections
	 */
	private $connections = [];

	/**
	 * @var array Map of connection names to type
	 */
	private $connectionNameTypeMap = [];

	/**
	 * Private constuctor to prevent instance instantiation
	 */
	private function __construct() {
		$this->connectionFactory = ConnectionFactory::getInstance();
	}

	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new ConnectionManager();
		}

		return self::$instance;
	}

	/**
	 * @param string $type Connection type
	 * @param $config array Array containing connection details
	 * @param string|null $name Optional name for multiple connections of the same type
	 */
	public function addConnection(string $type, $config, string $name = null) {
		try {
			$adapterClass = $this->connectionFactory->getConnectionAdapter($type);
		} catch (\Exception $e) {
			throw $e;
		}
		$adapter = new $adapterClass($config);
		if (!$name) {
			$this->connections[$type][] = $adapter;
		} else {
			$this->connections[$type][$name] = $adapter;
			$this->connectionNameTypeMap[$name] = $type;
		}
	}

	/**
	 * @param string $type Connection type
	 * @param string|null $name Connection name
	 * @return ConnectionInterface|null
	 */
	public function getConnection(string $type, string $name = null, bool $required = false) {
		if ($name !== null && strlen($name)) {
			if (key_exists($name, $this->connectionNameTypeMap)) {
				return $this->connections[$this->connectionNameTypeMap[$name]][$name];
			 } else {
				 return null;
			 }
		} elseif (key_exists($type, $this->connections)) {
			return array_values($this->connections[$type])[0];
		}
		if ($required) {
			throw new \Exception('No connection');
		}
		return null;
	}

}