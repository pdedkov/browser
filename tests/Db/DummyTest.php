<?php
namespace Tests\Browser\Db;

use Browser\Db\Dummy;

class DummyTest extends \PHPUnit_Framework_TestCase {
	public function testShouldRead() {
		$Db = new Dummy();
		
		$key = rand(0, 10000);
		
		$this->assertFalse($Db->readCache($key));
		
		$this->assertTrue($Db->writeCache($key, 'testval'));
		
		$this->assertFalse($Db->readCache($key));
	}
}