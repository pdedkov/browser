<?php
namespace Browser\Db;

use Config\Object as Base;

class Dummy extends Base implements Iface {
	/**
	 * Настройки по умолчанию
	 * @var array
	 */
	protected static $_defaults = [];

	public function __construct($config = []) {
		parent::__construct(__NAMESPACE__, $config);
	}

	public function writeCache($key, $value, $timeout = null) {
		return true;
	}

	public function readCache($key) {
		return false;
	}

	public function timeout($domain, $timeout = null) {
		return 0;
	}

	public function request($domain, $time = null) {
		return 0;
	}
}