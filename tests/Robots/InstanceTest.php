<?php
namespace Tests\Browser\Robots;

use Browser\Robots\Instance;

class InstanceTest extends \PHPUnit_Framework_TestCase {
	public function testShouldCheckDissallow() {
		$expected = [
			['agent' => '*', 'directive' => 'disallow', 'value' => '/users'],
			['agent' => '*', 'directive' => 'disallow', 'value' => '/inBills'],
			['agent' => '*', 'directive' => 'disallow', 'value' => '/?']
		];
		$this->assertEquals(Instance::get('http://miralinks.ru', 'disallow'), $expected);
	}
}