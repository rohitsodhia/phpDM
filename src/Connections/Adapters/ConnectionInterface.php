<?php

namespace phpDM\Connections\Interfaces;

/**
 * An abstract class to build database and cache connections off of
 * @package phpDM\Connections\Interfaces
 */
abstract class ConnectionInterface
{

	/**
	 * @var mixed Database or cache connection
	 */
	protected $connection;

	/**
	 * @var array Options for connection
	 */
	protected $options;

	/**
	 * ConnectionInterface constructor.
	 * @param array $configs Connection configs
	 */
	public function __construct(array $configs) {
		$this->connection = static::createConnection($configs);
		if (isset($configs['options'])) {
			$this->setOptions($configs['options']);
		}
	}

	/**
	 * Creates a database or cache connection
	 * @param array $config Connection configs
	 * @return mixed
	 */
	abstract public static function createConnection(array $config);

	/**
	 * Returns the connection object
	 * @return mixed
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * Set connection options
	 * @param array $options Connection options
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}

	/**
	 * Get connection option by name
	 * @param string $option Connection option
	 * @return mixed Connection option
	 */
	public function getOption(string $option) {
		return $this->options[$option];
	}

}