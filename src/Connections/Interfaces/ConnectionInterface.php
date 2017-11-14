<?php

namespace phpDM\Connections\Interfaces;

abstract class ConnectionInterface
{
	abstract public function createConnection(array $config);
}