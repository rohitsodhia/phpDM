<?php

namespace phpDM\Connections\Interfaces;

abstract class ConnectionInterface
{

	protected $connection;
	protected $options;

	public function __construct(array $configs) {
		$this->connection = static::createConnection($configs);
		if (isset($configs['options'])) {
			$this->setOptions($configs['options']);
		}
	}

	abstract public static function createConnection(array $config);

	public function getConnection() {
		return $this->connection;
	}

	public function setOptions(array $options) {
		$this->options = $options;
	}

	public function getOption(string $option) {
		return $this->options[$option];
	}

}