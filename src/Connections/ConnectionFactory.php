<?php

namespace phpDM\Connections;

use \Exception;
use phpDM\QueryBuilder\{
	MongoQueryBuilder, MysqlQueryBuilder
};
use phpDM\Models\{
	MongoModel,
	MysqlModel
};

/**
 * Class ConnectionFactory
 * @package phpDM\Connections
 */
class ConnectionFactory
{

	/**
	 * @var ConnectionFactory Singleton instance
	 */
	private static $instance = null;

	/**
	 * @var array List of added adapters
	 */
	private $connectionAdapters = [];

	/**
	 * @var array List of added query builders
	 */
	private $queryBuilders = [];

	/**
	 * Private constuctor to prevent instance instantiation
	 * Registers connection details depending on installed packages
	 */
	private function __construct() {
		if (class_exists('\MongoDB\Client')) {
			$this->registerConnection('mongo', [
				'adapter' => Adapters\MongoConnectionAdapter::class,
				'queryBuilder' => MongoQueryBuilder::class,
				'model' => MongoModel::class
			]);
		}
		if (class_exists('\PDO')) {
			$this->registerConnection('mysql', [
				'adapter' => Adapters\MysqlConnectionAdapter::class,
				'queryBuilder' => MysqlQueryBuilder::class,
				'model' => MysqlModel::class
			]);
		}
	}

	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new ConnectionFactory();
		}

		return self::$instance;
	}

	/**
	 * Registers a connection
	 * @param string $type
	 * @param array $config
	 */
	private function registerConnection(string $type, array $config) {
		$this->connectionAdapters[$type] = $config['adapter'];
		if (key_exists('queryBuilder', $config)) {
			$this->queryBuilders[$type] = $config['queryBuilder'];
		}
	}

	/**
	 * @return array
	 */
	public function getConnectionAdapters(): array {
		return $this->connectionAdapters;
	}

	/**
	 * @param string $type
	 * @return string
	 * @throws \Exception
	 */
	public function getConnectionAdapter(string $type): string {
		if (isset($this->connectionAdapters[$type])) {
			return $this->connectionAdapters[$type];
		} else {
			throw new Exception('Invalid connection type:' . $type);
		}
	}

	/**
	 * @return array
	 */
	public function getQueryBuilders(): array {
		return $this->queryBuilders;
	}

	/**
	 * @param string $type Query Builder type
	 * @return string
	 * @throws \Exception
	 */
	public function getQueryBuilder(string $type): string {
		if (isset($this->queryBuilders[$type])) {
			return $this->queryBuilders[$type];
		} else {
			throw new Exception('Invalid query builder');
		}
	}

}