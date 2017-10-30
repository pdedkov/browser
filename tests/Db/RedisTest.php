<?php
namespace Tests\Browser\Db;

use Browser\Db\Redis;

class RedisTest extends \PHPUnit_Framework_TestCase {
	public function testShouldRead() {
		if (!class_exists('Db\Redis\Instance')) {
			$this->markTestSkipped('Redis Driver unavailable');
		} else {
			$Db = new Redis();

			$key = rand(0, 10000);

			$this->assertFalse($Db->readCache($key));

			$this->assertTrue($Db->writeCache($key, 'testval'));

			$this->assertEquals($Db->readCache($key), 'testval');
		}
	}
}