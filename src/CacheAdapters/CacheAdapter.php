<?php

namespace phpDM\CacheAdapters;

use phpDM\Connections\Adapters\ConnectionInterface;
use phpDM\Models\BaseModel;

abstract class CacheAdapter
{

	protected static $type;
	protected $connection;
	protected $options = [];
	protected static $validOptions = [];
	protected static $defaultOptions = [];

	public function __construct(?ConnectionInterface $connection)
	{
		$this->connection = $connection;
		$this->setOptions($this->connection->getOptions());
	}


	/**
	 * Set adapter options
	 * @param array $options Adapter options
	 */
	public function setOptions(array $options) {
		if (count($this->options) === 0) {
			$defaults = static::$defaultOptions;
			$options = array_merge($defaults, $options);
		} else {
			$options = array_merge($this->getOptions(), $options);
		}
		foreach (array_keys($options) as $key) {
			if (array_search($key, static::$validOptions) === false) {
				unset($options[$key]);
			}
		}
		$this->options = $options;
	}

	/**
	 * @param string $option Option
	 * @param $value Option value
	 */
	public function setOption(string $option, $value) {
		if (array_search($option, self::$validOptions) !== false) {
			$this->options[$option] = $value;
		}
	}

	/**
	 * Get adapter option by name
	 * @param string $option Adapter option
	 * @return mixed Adapter option
	 */
	public function getOption(string $option) {
		if (key_exists($option, $this->options)) {
			return $this->options[$option];
		}
		return $this->connection->getOption($option);
	}

	/**
	 * @return array Adapter options
	 */
	public function getOptions() {
		return array_merge($this->connection->getOptions(), $this->options);
	}

	abstract public function storeModels(array $models);

	abstract public function getModel(string $model, $id);

	abstract public function getModels(string $model, $ids);

}