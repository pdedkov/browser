<?php
namespace Browser\Curl\Configurator;

// @OTODO использует cake-овские сессии, нужно универсализировать
if (CAKE):
	\App::uses('CakeSession', 'Model/Datasource');
	class Session extends \CakeSession {

	}
else:
	class Session extends \stdClass {
		public function read($key) {
			return @$_SESSION[$key];
		}
		public function write($key, $value) {
			$_SESSION[$key] = $value;
		}
	}
endif;

use Config\Object as Base;
use Config\Hash;

/**
 * Базовый класс конфигураторов
 */
class Configurator extends Base {
	protected $_Session = null;

	public function __construct($config = [], $namespace = null) {
		parent::__construct(__NAMESPACE__, $config);

		static::$_defaults = Hash::merge(self::$_defaults, static::$_defaults);

		if ($namespace) {
			$config = $this->_config(__NAMESPACE__, $config);
		} else {
			$namespace = __NAMESPACE__;
		}

		parent::__construct($namespace, $config);

		$this->_Session = new Session();
	}
}

/**
 * Конфигурация проксей
 *
 */
class Proxy extends Configurator implements Iface {
	protected static $_defaults = [
		'proxy' => []
	];

	/**
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Curl/Configurator/Browser\Curl\Configurator.Iface::configure()
	 */
	public function configure(&$handler, $options) {
		// проверяем глобальные настройки
		// опций может быть несколько
		if (is_string($options)) {
			$options = explode("|", $options);
		} else {
			$options = (array)$options;
		}

		// берём проскю из сессии
		$proxy = $this->_Session->read('Browser.proxy');

		$remember = false;
		foreach ($options as $option) {
			switch ($option) {
				case 'random':
					$proxies = $this->_config['proxy'];
					if (empty($proxies)) {
						trigger_error('Список проксей пуст', E_USER_WARNING);
						$proxy = null;
					} else {
						$proxy = $proxies[array_rand($proxies)];
					}
					break;
				case 'remember':
					$remember = true;
					break;
				case false:
					$proxy = null;
					break;
				default:
					if (strpos($option, ":") !== false) {
						$proxy = $option;
					} else {
						$proxy = null;
					}
					break;
			}
		}

		if ($remember) {
			$this->_Session->write('Browser.proxy', $proxy);
		}

		curl_setopt($handler, CURLOPT_PROXY, $proxy);

		return $handler;
	}
}

/**
 * Конфигурация User-Agent
 */
class Agent extends Configurator implements Iface {
	protected static $_defaults = [
		'agent' => 'Miralinks Robot',
		'agents' => [
			'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Maxthon/4.4.6.1000 Chrome/30.0.1599.101 Safari/537.36',
			'Mozilla/5.0 (Windows NT 5.1; rv:35.0) Gecko/20100101 Firefox/35.0',
			'Mozilla/5.0 (Windows NT 5.2; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0',
			'Mozilla/5.0 (Windows NT 6.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.89 Safari/537.36',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.137 Safari/537.36',
		]
	];

	/**
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Curl/Configurator/Browser\Curl\Configurator.Iface::configure()
	 */
	public function configure(&$handler, $options) {
		// опций может быть несколько
		$options = array_map('trim', explode("|", $options));

		// берём проскю из сессии
		$agent = $this->_Session->read('Browser.agent');

		$remember = false;
		foreach ($options as $option) {
			switch ($option) {
				case 'random':
					if (empty($this->_config['agents'])) {
						trigger_error('Список агентов пустой', E_USER_WARNING);
						$agent = null;
					} else {
						$agent = $this->_config['agents'][array_rand($this->_config['agents'])];
					}
					break;
				case 'default':
					$agent = $this->_config['agent'];
					break;
				case 'remember':
					$remember = true;
					break;
				default:
					$agent = $option;
					break;
			}
		}

		if ($remember) {
			$this->_Session->write('Browser.agent', $agent);
		}

		curl_setopt($handler, CURLOPT_USERAGENT, $agent);

		return $handler;
	}
}

/**
 * Конфигуратор заголовков
 */
class Headers extends Configurator implements Iface {
	/**
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Curl/Configurator/Browser\Curl\Configurator.Iface::configure()
	 */
	public function configure(&$handler, $options) {
		curl_setopt($handler, CURLOPT_HTTPHEADER, $options);
	}
}

/**
 * Конфигуратор IP-адреса
 */
class Ip extends Configurator implements Iface {
	/**
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Curl/Configurator/Browser\Curl\Configurator.Iface::configure()
	 */
	public function configure(&$handler, $options) {
		// опций может быть несколько
		$options = explode("|", $options);

		// берём проскю из сессии
		$ip = $this->_Session->read('Browser.ip');

		$remember = false;
		foreach ($options as $option) {
			switch ($option) {
				case 'random':
					$ips = $this->_config['ip'];
					$ip = $ips[array_rand($ips)];
					break;
				case 'remember':
					$remember = true;
					break;
				case false:
					$proxy = null;
					break;
				default:
					if (substr_count($option, ".") == 4) {
						$ip = $option;
					} else {
						$ip = null;
					}
					break;
			}
		}

		if ($remember) {
			$this->_Session->write('Browser.ip', $ip);
		}

		// Устанавливаем его в Curl
		if (!curl_setopt($handler, CURLOPT_INTERFACE, $ip)) {
			throw new Exception('Недоступный IP адрес', Exception::INVALID_ADDRESS);
		}

		return $handler;
	}
}

/**
 * Конфигуратор Cookie
 */
class Cookie extends Configurator implements Iface {
	/**
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Curl/Configurator/Browser\Curl\Configurator.Iface::configure()
	 */
	public function configure(&$handler, $options) {
		// опций может быть несколько
		$options = explode("|", $options);

		// берём проскю из сессии
		$cookie = $this->_Session->read('Browser.cookie');

		$remember = false;
		foreach ($options as $option) {
			switch ($option) {
				case 'random':
					if (empty($cookie) || !file_exists($cookie)) {
						$cookie = tempnam(TMP, 'cookie_');
					}
					break;
				case 'remember':
					$remember = true;
					break;
				case false:
				default:
					if (file_exists($cookie)) {
						@unlink($cookie);
					}
					$cookie = null;
					break;
			}
		}

		if ($remember) {
			$this->_Session->write('Browser.cookie', $cookie);
		}
		
		if (!empty($cookie)) {
			curl_setopt($handler, CURLOPT_COOKIEFILE, $cookie);
			curl_setopt($handler, CURLOPT_COOKIEJAR,  $cookie);
		}

		return $handler;
	}
}

/**
 * Конфигуратор ssl
 */
class Ssl extends Configurator implements Iface {
	public function configure(&$handler, $options) {
		if (empty($options['cainfo']) || empty($options['cert']) || empty($options['key'])) {
			throw new Exception('Необходимо указать сертификаты', Exception::MISSING_OPTIONS);
		}

		curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, 0);

		curl_setopt($handler, CURLOPT_CAINFO, $options['cainfo']);
		curl_setopt($handler, CURLOPT_SSLCERT, $options['cert']);
		curl_setopt($handler, CURLOPT_SSLKEY, $options['key']);

		return $handler;
	}
}

/**
 * Опции curl-а
 */
class Options extends Configurator implements Iface {
	public function configure(&$handler, $options) {
		if (!is_array($options)) {
			throw new Exception('Неверный параметры curl', Exception::INVALID_OPTIONS);
		}
		curl_setopt_array($handler, $options);
	}
}

/**
 * dummy для disabled опции
 */
class Dummy extends Configurator implements Iface {
	public function configure(&$handler, $options) {
		return true;
	}
}

/**
 * Фабрика генерации конфигураторов
 *
 */
class Factory extends Base {
	protected static $_default = [
		'disabled' => []
	];

	public function __construct($config = []) {
		parent::__construct(__NAMESPACE__, $config);
	}

	/**
	 * Генерируем объект в зависимости от переданного типа опции конфигурирования
	 *
	 * @param string $type type
	 * @throws Exception
	 * @return Configurator
	 */
	public function load($type) {
		// проверяем не отключён ли конфигуратор
		if (!empty($this->_config['disabled']) && in_array($type, $this->_config['disabled'])) {
			return new Dummy();
		}

		$name = ucfirst($type);

		$class = "\Browser\Curl\Configurator\\{$name}";

		if (!class_exists($class)) {
			throw new Exception('Неверный тип конфигуратора: ' . $type, Exception::INVALID_CONFIGURATOR);
		}

		$Configurator = new $class();

		if (is_a($Configurator, '\Browser\Curl\Configurator\Iface')) {
			return $Configurator;
		} else {
			return new Dummy();
		}
	}
}