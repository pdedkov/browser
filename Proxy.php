<?php
namespace Browser;

use Uri\Instance as Uri;
use Config\Object as Base;
use Logger\Instance as L;

class Proxy extends Base {
	protected $_Curl = null;

	/**
	 * Дополнительная БД для хранения всего подряд
	 *
	 * @var mixed
	 */
	protected $_Db = null;

	protected static $_defaults = [
		'agent'	=> 'Browser',
		'Db'	=> [
			'driver' => 'Redis',
			'config' => []
		]
	];

	public function __construct($config = []) {
		parent::__construct(__NAMESPACE__, $config);

		$this->_Curl = new Curl\Instance();

		$Db = "\Browser\Db\\{$this->_config['Db']['driver']}";

		$this->_Db = new $Db($this->_config['Db']['config']);
	}

	/**
	 * magic __call
	 * @param вызывем метод $method
	 * @param unknown_type $arguments
	 */
	public function __call($method, $arguments) {
		return call_user_func_array([$this->_Curl, $method], $arguments);
	}

	/**
	 * Выполняем запрос
	 *
	 * @param string $url url запроса
	 * @param string $type тип запроса
	 * @param [] $data данные для запроса
	 * @param bool $direct идём сразу на сайт, или обрабатываем crawl-delay
	 * @param bool $cache пытаемся взять из кэша или нет
	 *
	 * @return mixed результат запроса
	 */
	public function request($url, $type = 'GET', $data = [], $direct = true, $cache = false) {
		$response = null;

		if ($type == 'GET') {
			if ($cache) {
				// пытаемся прочитать содержимое из кэша
				$response = $this->_Db->readCache($url);
			}

			// если ответ не закешировался
			if (empty($response['content'])) {
				if (!$direct) {
					$this->_timeout($url);
				}

				$response = $this->_Curl->request($url, $type, $data);

				// кэшируем ответ на потом только если не было ошибок
				if (empty($response['error']) && $cache) {
					$this->_Db->writeCache($url, $response);
				}
			}

			return $response;
		} else {
			return $this->_Curl->request($url, $type, $data);
		}
	}

	/**
	 * Timeout перед очередным запросом
	 * @param string $url
	 * @return bool
	 */
	protected function _timeout($url) {
		$domain = Uri::getHost($url);
		// пытаемся считать timeout для текущего домена из БД
		try {
			$timeout = $this->_Db->timeout($domain);
		} catch (\Exception $e) {
			$timeout = null;
		}

		try {
			if (!is_float($timeout)) {
				$timeouts = Robots\Instance::get($url, 'crawl-delay', $this->_config['agent']);
				$timeout = (is_array($timeouts) && isset($timeouts[0]['value'])) ? floatval($timeouts[0]['value']) : 0.00;
				// ставим timeout
				try {
					$timeout = $this->_Db->timeout($domain, $timeout);
				} catch (\Exception $e) {
				}
			}

			// получаем время последнего запроса
			try {
				$last = $this->_Db->request($domain);
			} catch (\Exception $e) {
				// если ошибка, то делаем вид, что только что сделали запрос
				$last = microtime(true);
			}

			$now = microtime(true);
			// переводим в микросекунды
			$diff = $last - $now + $timeout;


			// смотря что больше текущий момент или последний запрос (он может быть в будущем, т.к. мы резервируем время
			$next = max([$now, $last]);

			L::log($now . " -> " . $domain . " => " . $next . " => " . $last . " => " . $timeout . " => " . $diff, L::LOG_INFO, 'sleep-timeout');
			if ($diff > 0) {
				$next += $diff;
			}

			// небольшой хак, для того, что другие клиенты считали этот запрос выполенным, иначе может получить пачка
			// с одной задержкой
			$this->_Db->request($domain, $next);

			if ($diff > 0) {
				$sleep = ($next - $now)*1000000;
				L::log($now . " -> " . $domain . " => " . $next . " => " . $sleep . " -> wait", L::LOG_INFO, 'sleep-timeout');
				usleep($sleep);
			}
			L::log($now . " -> " . $domain . " => " . microtime(true) . " -> slept", L::LOG_INFO, 'sleep-timeout');


			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
}

