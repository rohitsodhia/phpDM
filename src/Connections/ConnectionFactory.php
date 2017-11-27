<?php

namespace phpDM\Connections;

class ConnectionFactory
{

	private static $connectionInterfaces = [];
	private static $queryBuilders = [];

	public static function init() {
		self::registerConnection('mongo', [
			'interface' => Interfaces\MongoConnectionInterface::class,
			'queryBuilder' => \phpDM\QueryBuilder\MongoQueryBuilder::class,
			'model' => \phpDM\Models\MongoModel::class
		]);
		self::registerConnection('mysql', [
			'interface' => Interfaces\MysqlConnectionInterface::class,
			'queryBuilder' => \phpDM\QueryBuilder\MysqlQueryBuilder::class,
			'model' => \phpDM\Models\MysqlModel::class
		]);
	}

	public static function registerConnection(string $type, array $config) {
		self::$connectionInterfaces[$type] = $config['interface'];
		self::$queryBuilders[$type] = $config['queryBuilder'];
	}

	public static function getConnectionInterfaces(): array {
		return self::$connectionInterfaces;
	}

	public static function getConnectionInterface(string $type) {
		if (isset(self::$connectionInterfaces[$type])) {
			return self::$connectionInterfaces[$type];
		} else {
			throw Exception('Invalid connection');
		}
	}

	public static function getQueryBuilders(): array {
		return self::$queryBuilders;
	}

	public static function getQueryBuilder(string $type) {
		if (isset(self::$queryBuilders[$type])) {
			return self::$queryBuilders[$type];
		} else {
			throw Exception('Invalid query builder');
		}
	}

}