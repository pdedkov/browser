<?php
namespace Browser\Db;

use Config\Hash;

class Ssdb extends Redis {
	/**
	 * Настройки по умолчанию
	 * @var array
	 */
	protected static $_defaults = [
		'server' => [
			'host'		=> 'localhost',
			'port'		=> 8888,
			// префикс записи
			'prefix' => 'browser'
		],
		// timeout по умолчанию
		'timeout' => 3600
	];

	public function __construct($config = []) {
		static::$_defaults = Hash::merge(self::$_defaults, static::$_defaults);

		$config = $this->_config(__NAMESPACE__, $config);
		parent::__construct(null, $config);
	}
}