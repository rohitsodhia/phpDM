<?php

namespace phpDM\CacheAdapters;

use \Exception;
use phpDM\Connections\ConnectionManager;

/**
 * Class CacheFactory
 * @package phpDM\CacheAdapters
 */
class CacheFactory
{

	private static $adapters = [];

	static function init() {
		if (class_exists('\Redis')) {
			self::registerAdapter('redis', RedisAdapter::class);
		}
	}

	static function registerAdapter(string $type, string $class) {
		self::$adapters[$type] = $class;
	}

	static function getCacheAdapter($type, $name) {
		if (count(self::$adapters) === 0) {
			self::init();
		}
		return new self::$adapters[$type](ConnectionManager::getConnection($type, $name));
	}

}