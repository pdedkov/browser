<?php
namespace Browser;

use Config\Singleton as Base;
use Config\Hash;

/**
 * Браузер для парсинга сайтов
 */
class Instance extends Base {
	const MIX_LAST = PHP_INT_MAX;
	const MIX_FIRST = 0;
	const MIX_DEFAULT = 100;

	/**
	 * proxy
	 *
	 * @var object
	 */
	protected $_Proxy = null;

	/**
	 * Массив установленных опций
	 * @var []
	 */
	protected $_vars = [];

	/**
	 * Примеси обработчики callback
	 * @var array
	 */
	protected $_mixins = [];

	/**
	 * Массив содержащий приоритеты миксов для выполнения (name => priority)
	 */
	protected $_mixPriorities = [];

	/**
	 * Базовые настройки
	 * @var array
	 */
	protected static $_defaults = [
		'retry'		=> false,
		'headers'	=> false,
		'exception'	=> true,
		'return'	=> 'content',
		'cache'		=> false,
		'direct'	=> true,
		'sleep'	=> 2
	];

	/**
	 * массив для хранения дампа опции
	 * @var array
	 */
	protected $_dump = [];

	protected function __construct($namespace = null) {
		parent::__construct($namespace);
		// перечитываем дефолтные опции конфиругации
		$this->_Proxy = new Proxy();

		$this->_Proxy->configure();
	}

	protected static $_Instance = null;

	public static function getInstance() {
		if (is_null(self::$_Instance)) {
			self::$_Instance = new self(__NAMESPACE__);
		}

		return self::$_Instance;
	}

	/**
	 * Вызываем статический метод
	 *
	 * @param string $name имя метода
	 * @param [] $arguments массив, содержащий переданные методу аргументы
	 * @return mixed
	 */
	public static function __callStatic($name, $arguments) {
		$_this = self::getInstance();

		$method = '_' . strtolower($name);

		if (!method_exists($_this, $method)) {
			throw new Exception("Метод {$name} не определён");
		}

		// миксируем то, что перед
		$mixedArguments = $_this->_mix('before', $arguments, $name);

		// расчитываем количество попыток
		$count = !empty($_this->_config['retry'])
			? (is_numeric($_this->_config['retry']) ? $_this->_config['retry'] : 2)
			: 0
		;

		// выполняем действие
		do {
			$result = $_this->{$method}($mixedArguments);

			if (empty($result['error'])) {
				break;
			}

			$count--;

			if ($count > 0 && $_this->_config['retry']) {
				sleep($_this->_config['sleep']);
				// значит произошла какая-то ошибка
				$_this->_Proxy->reconfigure(['proxy', 'ip', 'agent']);
			} else {
				// ругаимся
				if ($_this->_config['exception']) {
					throw new Exception($result['error'], $result['headers']['http_code']);
				}
			}
		} while ($count > 0);

		// миксируем то, что после действия
		$result = $_this->_mix('after', $result, $arguments);

		// определяем что именно нужно вернуть
		switch ($_this->_config['return']) {
			case 'content':
				return $result['content'];
				break;
			case 'headers':
				return $result['headers'];
				break;
			case 'all':
			default:
				return $result;

		}
	}

	/**
	 * magic get
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		if (isset($this->_vars[$name])) {
			return $this->_vars[$name];
		} else {
			return null;
		}
	}

	/**
	 * magic set
	 */
	public function __set($name, $value) {
		$this->_vars[$name] = $value;
	}

	/**
	 * Делаем get-запрос
	 *
	 */
	protected function _get($data = []) {
		return $this->_Proxy->request($data[0], 'GET', !empty($data[1]) ? $data[1] : [], !empty($this->_config['direct']), !empty($this->_config['cache']));
	}

	/**
	 * Делаем POST-запрос
	 */
	protected function _post($data = []) {
		return $this->_Proxy->request($data[0], 'POST', !empty($data[1]) ? $data[1] : [], true);
	}

	/**
	 * Делаем PUT-запрос
	 */
	protected function _put($data = []) {
		return $this->_Proxy->request($data[0], 'PUT', !empty($data[1]) ? $data[1] : [], true);
	}
	
	/**
	 * Делаем delete-запрос
	 *
	 */
	protected function _delete($data = []) {
		return $this->_Proxy->request($data[0], 'DELETE', !empty($data[1]) ? $data[1] : [], true);
	}

	/**
	 * Добавляем обработчик примересей
	 *
	 * @param mixed $Mixin
	 * @param int $priority приоритет выполнения миксов (0 - default, -N - вниз, +N - вверх)
	 */
	public static function mix($Mixin = null, $priority = self::MIX_DEFAULT, $data = []) {
		$_this = self::getInstance();

		if (is_object($Mixin)) {
			$name = $Mixin->name();
			$_this->_mixins[$name] = $Mixin;
		} elseif (is_array($Mixin)) {
			if (!empty($Mixin['object']) && is_object($Mixin['object'])) {
				if (!empty($Mixin['name'])) {
					$name = $Mixin['name'];
					$_this->_mixins[$name] = $Mixin['object'];
				} else {
					$name = $Mixin['object']->name();
					$_this->_mixins[$name] = $Mixin['object'];
				}
			} else {
				throw new Exception('Неверный объект для микширования', Exception::INVALID_MIXIN);
			}
		} elseif (is_string($Mixin)) {
			$name = $Mixin;
			$_this->_mixins[$name] = null;
		} else {
			throw new Exception('Неверный объект для микширования', Exception::INVALID_MIXIN);
		}

		$_this->_mixPriorities[$name] = $priority;

		return true;
	}

	/**
	 * Удаляем примесь
	 *
	 * @param mixed $mixin название примеси
	 */
	public static function unmix($mixin = null) {
		$_this = self::getInstance();

		if (empty($mixin)) {
			$_this->_mixins = [];
		} else {
			$mixin = (array)$mixin;
			foreach ($mixin as $mix) {
				unset($_this->_mixins[(string)$mix], $_this->_mixPriorities[(string)$mix]);
			}
		}

		return true;
	}

	/**
	 * Вызов callback-ов примесей
	 * @param string $type тип callback-а
	 * @param mixed $data данные для примеси
	 */
	protected function _mix($action, $data, $arguments = null) {
		$result = $data;
		// сортируем приоритеты миксов
		if (!empty($this->_mixins) && !empty($this->_mixPriorities)) {
			asort($this->_mixPriorities);

			foreach ($this->_mixPriorities as $name => $priority) {
				if (array_key_exists($name, $this->_mixins)) {
					$Mixin = $this->_mixins[$name];
					if (!is_object($Mixin)) {
						$name = ucfirst($name);
						$name = "Browser\Mixin\\{$name}";
						$Mixin = new $name();
					}
					$data = $Mixin->{$action}($this, $data, $arguments);
				}
			}
		}

		return $data;
	}

	/**
	 * Конфигурация браузера
	 *
	 * @param array $curl опции настройки curl-а
	 * @param array $config опции самого браузера
	 * @param bool $clear очищаем предыдущие настройки
	 * @param bool $saveConfig сохраняем конфиг перед настройкой
	 * @return bool
	 * @throw Browser\Exception
	 */
	public static function configure($curl = [], $config = [], $clear = true, $saveConfig = false) {
		$_this = self::getInstance();

		// очищаем
		if ($clear) {
			$_this->_Proxy->clear($saveConfig);
		}

		if (!empty($curl)) {
			$_this->_Proxy->configure($curl);
		}

		// конфигурируем себя
		if (!empty($config)) {
			$_this->_config = Hash::merge(static::$_defaults, $config);
		}

		return true;
	}


	/**
	 * Пакуем опции
	 *
	 * @return bool
	 */
	public static function pack() {
		$_this = self::getInstance();

		array_unshift($_this->_dump,  ['config' => $_this->_config, 'curl' => $_this->_Proxy->configure()]);

		return true;
	}

	/**
	 * Распаковываем опции
	 */
	public static function unpack() {
		$_this = self::getInstance();

		$dump = array_shift($_this->_dump);

		if (!empty($dump)) {
			$_this->configure($dump['curl'], $dump['config']);
		}

		return true;
	}
}