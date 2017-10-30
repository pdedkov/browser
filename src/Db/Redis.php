<?php
namespace Browser\Db;

use Db\Redis\Instance as Base;
use Config\Hash;

class Redis extends Base implements Iface {
	/**
	 * Настройки по умолчанию
	 * @var array
	 */
	protected static $_defaults = [
		'server' => [
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

	/**
	 *
	 * Сохраняем значение в БД, перед этим преобразоываем в строку
	 *
	 * @param string $key ключ
	 * @param mixed $value значение
	 * @return bool
	 */
	public function writeCache($key, $value, $timeout = null) {
		try {
			$timeout = empty($timeout) ? $this->_config['timeout'] : $timeout;
			if (!is_string($value)) {
				$value = serialize($value);
			}

			return $this->setex($this->_key($key), $timeout, $value);
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Чтение значение и его преобразование, если нужно
	 *
	 * @param string $key ключ
	 * @return mixed
	 */
	public function readCache($key) {
		try {
			$value = $this->get($this->_key($key));

			$val = @unserialize($value);
			if ($val !== false) {
				$value = $val;
			}

			return $value;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * timeout для прокси как getter так и setter
	 *
	 * @param float $timeout таймаут который нужно уставить, если вообще нужно
	 */
	public function timeout($domain, $timeout = null) {
		if (is_numeric($timeout)) {
			$this->setex("timeout:{$domain}", $this->_config['timeout'], $timeout);
		}

		return $this->get("timeout:{$domain}");
	}

	/**
	 * Ставим читаем время последнего запроса
	 *
	 * @param string $domain имя домена
	 * @param float $time время последнего запроса
	 * @return float время последнего запроса
	 */
	public function request($domain, $time = null) {
		if (!empty($time)) {
			$this->set("request:{$domain}", $time);
		}

		return (float)$this->get("request:{$domain}");
	}
}