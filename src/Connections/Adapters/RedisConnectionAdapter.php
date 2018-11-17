<?php

namespace phpDM\Connections\Adapters;

class RedisConnectionAdapter extends ConnectionInterface
{

	protected static $validOptions = [
		'namespace',
		'separator',
		'ttl',
	];

	public static function createConnection(array $config = [])
	{
		$redis = new \Redis();
		if (!isset($config['port'])) {
			$config['port'] = 6379;
		}
		$redis->connect($config['host'], $config['port']);
//		$redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
		return $redis;
	}

}