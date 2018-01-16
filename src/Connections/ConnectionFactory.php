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
	 * @var array List of added interfaces
	 */
	private static $connectionInterfaces = [];

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
				'interface' => Interfaces\MongoConnectionInterface::class,
				'queryBuilder' => MongoQueryBuilder::class,
				'model' => MongoModel::class
			]);
		}
		if (class_exists('\PDO')) {
			self::registerConnection('mysql', [
				'interface' => Interfaces\MysqlConnectionInterface::class,
				'queryBuilder' => MysqlQueryBuilder::class,
				'model' => MysqlModel::class
			]);
		}
	}

	/**
	 * Registers a connection, 
	 * @param string $type
	 * @param array $config
	 */
	public static function registerConnection(string $type, array $config) {
		self::$connectionInterfaces[$type] = $config['interface'];
		self::$queryBuilders[$type] = $config['queryBuilder'];
	}

	/**
	 * @return array
	 */
	public static function getConnectionInterfaces(): array {
		return self::$connectionInterfaces;
	}

	/**
	 * @param string $type
	 * @return string
	 * @throws \Exception
	 */
	public static function getConnectionInterface(string $type): string {
		if (isset(self::$connectionInterfaces[$type])) {
			return self::$connectionInterfaces[$type];
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
	 * @return QueryBuilder
	 * @throws \Exception
	 */
	public static function getQueryBuilder(string $type): QueryBuilder {
		if (isset(self::$queryBuilders[$type])) {
			return self::$queryBuilders[$type];
		} else {
			throw new Exception('Invalid query builder');
		}
	}

}