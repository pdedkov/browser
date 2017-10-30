<?php
namespace Browser;

use Url\Instance as Uri;
use Config\Object as Base;

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
			'driver' => 'Dummy',
			'config' => []
		],
		'maxDelay' => 2
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
	 * @return mixed
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


			$wait = $cached = false;
			$start = $end = 0;

			// если ответ не закешировался
			if (empty($response['content'])) {
				if (!$direct) {
					$wait = true;
					$start = microtime(true);
					$this->_timeout($url);
					$end = microtime(true) - $start;
				}

				$response = $this->_Curl->request($url, $type, $data);

				// кэшируем ответ на потом только если не было ошибок
				if (empty($response['error']) && $cache) {
					$this->_Db->writeCache($url, $response);
				}
			} else {
				$cached = true;
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
			if (!is_numeric($timeout)) {
				$timeouts = Robots\Instance::get($url, 'crawl-delay', $this->_config['agent']);
				if (!empty($timeouts[0]) && array_key_exists('value', $timeouts[0])) {
					$timeout = (int)trim($timeouts[0]['value']);
					// ставим timeout
					try {
						if ($timeout > $this->_config['maxDelay']) {
							$timeout = $this->_config['maxDelay'];
						}

						$timeout = $this->_Db->timeout($domain, $timeout);
					} catch (\Exception $e) {
					}
				}

			}
			if ($timeout <= 0) {
				return true;
			}
			// получаем время последнего запроса
			try {
				$last = $this->_Db->request($domain);
			} catch (\Exception $e) {
				// если ошибка, то делаем вид, что только что сделали запрос
				$last = time();
			}


			$now = time();
			$diff = $last - $now + $timeout;

			// смотря что больше текущий момент или последний запрос (он может быть в будущем, т.к. мы резервируем время
			$next = max([$now, $last]);

			if ($diff > 0) {
				$next += $timeout;
			}

			// небольшой хак, для того, что другие клиенты считали этот запрос выполенным, иначе может получить пачка
			// с одной задержкой
			$this->_Db->request($domain, $next);

			if ($diff > 0) {
				$sleep = $next - $now;
				sleep((int)$sleep);
			}

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
}

