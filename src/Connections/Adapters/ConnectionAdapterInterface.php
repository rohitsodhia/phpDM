<?php

namespace phpDM\Connections\Adapters;

/**
 * An abstract class to build database and cache connections off of
 * @package phpDM\Connections\Adapters
 */
abstract class ConnectionAdapterInterface
{

	/**
	 * @var mixed Database or cache connection
	 */
	private $connection;

	/**
	 * @var array Options for connection
	 */
	private $options = [];
	
	/**
	 * @var array Valid options
	 */
	private const VALID_OPTIONS = [
		'case',
	];

	/**
	 * @var array Default options
	 */
	private const DEFAULT_OPTIONS = [];

	/**
	 * ConnectionInterface constructor.
	 * @param array $configs Connection configs
	 */
	public function __construct(array $configs, array $options = []) {
		$this->setOptions($options);
		$this->connection = $this->createConnection($configs);
	}

	/**
	 * Creates a database or cache connection
	 * @param array $config Connection configs
	 * @return mixed
	 */
	abstract protected function createConnection(array $config);

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
		$options = array_merge(self::DEFAULT_OPTIONS, $options);
		foreach (array_keys($options) as $key) {
			if (array_search($key, self::VALID_OPTIONS) === false) {
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