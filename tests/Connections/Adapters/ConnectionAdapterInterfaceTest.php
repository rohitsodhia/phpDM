<?php

include(__DIR__ . '/../../../src/Connections/Adapters/ConnectionAdapterInterface.php');

use PHPUnit\Framework\TestCase;
use phpDM\Connections\Adapters\ConnectionAdapterInterface;


class TestConnection { }

class ConnectionAdapterInterfaceTest extends TestCase
{

	function testGetConnection() {
		$interface = $this->getMockForAbstractClass(ConnectionAdapterInterface::class, [[]]);
		$interface->method('createConnection')
			->willReturn(new TestConnection());
		$connection = $interface->createConnection();
		// $this->assertInstanceOf(TestConnection, $connection);
	}

}