<?php
namespace Tests\Browser;


use Browser\Instance;

class InstanceTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @test Instance::get
	 */
	public function testShouldGetPageOnce() {
		Instance::configure(
			array('proxy' => 'random', 'agent' => 'default'),
			array('return' => 'all', 'exception' => true)
		);
		$result = Instance::get('http://miralinks.ru');
		$this->assertEquals(200, $result['headers']['http_code']);
	}


	public function testShouldGetPageGziped() {
		Instance::configure(
			array('proxy' => 'random', 'agent' => 'BrowserTest'),
			array('return' => 'all', 'exception' => true,)
		);

		$result = Instance::get('cikavosti.com/naybilshiy-na-shodi-virobnik-budmaterialiv-dlya-vsiyeyi-ukrayini-z-harkova/');
		$this->assertEquals(200, $result['headers']['http_code']);
		$result = Instance::get('mail.ru');
		$this->assertEquals(200, $result['headers']['http_code']);

		Instance::configure(
			array('proxy' => 'random', 'options' => array(CURLOPT_NOBODY => true), 'agent' => 'BrowserTest'),
			array('return' => 'headers', 'exception' => true, 'retry' => true)
		);

		$result = Instance::get('cikavosti.com/naybilshiy-na-shodi-virobnik-budmaterialiv-dlya-vsiyeyi-ukrayini-z-harkova/');
		$this->assertEquals(200, $result['http_code']);
		$result = Instance::get('mail.ru');
		$this->assertEquals(200, $result['http_code']);

	}

	/**
	 * @test Instance::get
	 */
	public function testShouldGetPage() {
		Instance::configure(
			array('proxy' => 'random', 'options' => array(CURLOPT_NOBODY => true), 'agent' => 'BrowserTest'),
			array('return' => 'headers', 'exception' => true, 'retry' => true)
		);

		Instance::mix('Tidy', Instance::MIX_FIRST);

		$result = Instance::get('http://mail.ru');

		Instance::unmix('Tidy');

		$this->assertEquals(200, $result['http_code']);
	}

	/**
	 * @test Instance::get
	 */
	public function testShouldGetPageAndCache() {
		Instance::configure([], ['cache' => true]);

		Instance::get('http://miralinks.ru');
	}

	public function testShouldPackOptions() {
		// конфигурируем
		Instance::configure(
			array('proxy' => 'random', 'options' => array(CURLOPT_NOBODY => true), 'agent' => 'BrowserTest'),
			array('return' => 'headers', 'exception' => true, 'retry' => true)
		);

		// пакуем
		Instance::pack();
		// меняем конфигурацию
		Instance::configure([], ['return' => 'content']);

		// запрос
		$result = Instance::get('http://miralink.ru');

		// распаковываем
		Instance::unpack();

		// запрос
		$result = Instance::get('http://miralinks.ru');

		$this->assertEquals(200, $result['http_code']);
	}

	public function testShouldGoWithDelay() {
		// конфигурируем
		Instance::configure(
			array('proxy' => 'random', 'options' => array(CURLOPT_NOBODY => true), 'agent' => 'BrowserTest'),
			array('return' => 'headers', 'exception' => true, 'retry' => true, 'direct' => false)
		);

		// запрос
		$result = Instance::get('http://miralinks.ru');

		$this->assertEquals(200, $result['http_code']);
	}
}