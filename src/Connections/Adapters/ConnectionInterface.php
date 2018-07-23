<?php

namespace phpDM\Connections\Adapters;

/**
 * An abstract class to build database and cache connections off of
 * @package phpDM\Connections\Adapters
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
	protected $options = [];

	/**
	 * @var array Valid options
	 */
	protected static $validOptions = [];

	/**
	 * @var array Default options
	 */
	protected static $defaultOptions = [];

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
		$defaults = static::$defaultOptions;
		$options = array_merge($defaults, $options);
		foreach (array_keys($options) as $key) {
			if (array_search($key, static::$validOptions) === false) {
				unset($options[$key]);
			}
		}
		$this->options = $options;
	}

	/**
	 * Get connection option by name
	 * @param string $option Connection option
	 * @return mixed Connection option
	 */
	public function getOption(string $option) {
		return key_exists($option, $this->options) ? $this->options[$option] : null;
	}

	/**
	 * Get connection options
	 * @return array Connection options
	 */
	public function getOptions() {
		return $this->options;
	}

}