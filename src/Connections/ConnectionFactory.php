<?php

namespace phpDM\Connections;

use \Exception;
use phpDM\QueryBuilder\{
	MongoQueryBuilder, MysqlQueryBuilder, QueryBuilder
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
	 * @var array List of added adapters
	 */
	private static $connectionAdapters = [];

	/**
	 * @var array List of added query builders
	 */
	private static $queryBuilders = [];

	/**
	 * Registers connection details depending on installed packages
	 */
	public static function init() {
		if (class_exists('\MongoDB\Client')) {
			self::registerConnection('mongo', [
				'adapter' => Adapters\MongoConnectionAdapter::class,
				'queryBuilder' => MongoQueryBuilder::class,
				'model' => MongoModel::class
			]);
		}
		if (class_exists('\PDO')) {
			self::registerConnection('mysql', [
				'adapter' => Adapters\MysqlConnectionAdapter::class,
				'queryBuilder' => MysqlQueryBuilder::class,
				'model' => MysqlModel::class
			]);
		}
	}

	/**
	 * Registers a connection
	 * @param string $type
	 * @param array $config
	 */
	public static function registerConnection(string $type, array $config) {
		self::$connectionAdapters[$type] = $config['adapter'];
		if (key_exists('queryBuilder', $config)) {
			self::$queryBuilders[$type] = $config['queryBuilder'];
		}
	}

	/**
	 * @return array
	 */
	public static function getConnectionAdapters(): array {
		return self::$connectionAdapters;
	}

	/**
	 * @param string $type
	 * @return string
	 * @throws \Exception
	 */
	public static function getConnectionAdapter(string $type): string {
		if (isset(self::$connectionAdapters[$type])) {
			return self::$connectionAdapters[$type];
		} else {
			throw new Exception('Invalid connection:' . $type);
		}
	}

	/**
	 * @return array
	 */
	public static function getQueryBuilders(): array {
		return self::$queryBuilders;
	}

	/**
	 * @param string $type Query Builder type
	 * @return string
	 * @throws \Exception
	 */
	public static function getQueryBuilder(string $type): string {
		if (isset(self::$queryBuilders[$type])) {
			return self::$queryBuilders[$type];
		} else {
			throw new Exception('Invalid query builder');
		}
	}

}