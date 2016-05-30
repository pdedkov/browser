<?php
namespace Browser\Curl;

use Config\Object as Base;
use Uri\Instance as Uri;

class Instance extends Base {
	protected $_curl = null;

	/**
	 * Опции конфигурации
	 * @var array
	 */
	protected static $_defaults = [
		'proxy' => false,
		'ip' => false,
		'headers' => [],
		'cookie' => false,
		'agent' => 'default',
		'options' => [
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FRESH_CONNECT => true,
			CURLOPT_HEADER => false,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_ENCODING => 'deflate,gzip',
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false
		]
	];

	/**
	 * URL последнего запроса
	 * @var string
	 */
	protected $_url = null;

	protected $_Configurator = null;

	public function __construct($config = []) {
		parent::__construct(__NAMESPACE__, $config);
		
		$this->_curl = curl_init();

		$this->configure();
	}

	/**
	 * Основные настройки курла
	 *
	 * @param array $options опции curl-а
	 * @return bool
	 */
	public function configure($config = []) {
		$this->_Configurator = new Configurator\Factory();

		$this->_config = $this->_config(__NAMESPACE__, $config);

		// конфигурируем
		foreach ($this->_config as $option => $params) {
			$this->_Configurator->load($option)->configure($this->_curl, $params);
		}

		return $this->_config;
	}

	/**
	 * Конфигурируем наш объект
	 *
	 * @param string $namespace пространство имён текущего вызова
	 * @param array $config дополнительные опции
	 * @return array конфиг готовый
	 */
	protected function _config($namespace, $config) {
		// это хак, который сохраняет curl options
		$options = !empty($config['options']) ? $config['options'] : [];

		unset($config['options']);

		$config = parent::_config($namespace, $config);

		if (!empty($options)) {
			if (empty($config['options'])) {
				$config['options'] = $options;
			} else {
				$config['options'] = $options + $config['options'];
			}
		}

		return $config;
	}

	/**
	 * Просто переконфигурирем curl на основе старых опции
	 *
	 * @param array массив опций, которые требуют переконфигурации
	 *
	 * @return bool
	 */
	public function reconfigure($options = []) {
		foreach ($this->_config as $option => $params) {
			if (in_array($option, $options)) {
				$this->_Configurator->load($option)->configure($this->_curl, $params);
			}
		}
		return true;
	}

	/**
	 * Сбрасываем curl
	 *
	 * @param bool $save
	 */
	public function clear($saveConfig = false) {
		curl_close($this->_curl);

		$this->_curl = curl_init();
		if (!$saveConfig) {
			$this->configure();
		} else {
			$this->reconfigure();
		}

		return true;
	}

	/**
	 * Непосредственно делаем запрос
	 *
	 * @param string $url куда делаем запрос
	 * @param string $type тип запроса
	 * @param array $data данные запроса (для post-put)
	 * @param bool $encode кодирование входных данных
	 *
	 * @return mixed
	 */
	public function request($url, $type = 'GET', $data = [], $encode = false) {
		$method = '_' . mb_convert_case($type, MB_CASE_LOWER);
		if (!method_exists($this, $method)) {
			throw new Exception('Неверный тип запроса', Exception::WRONG_METHOD);
		}

		// устанавливаем куда
		$this->url($url);

		// выполняем запрос
		return $this->{$method}($data, $encode);
	}

	/**
	 * Устанавливаем url для следующего запроса
	 *
	 * @param string $url
	 * @return bool
	 */
	public function url($url) {
		$this->_url = Uri::toIdn($url);
		// кодируем всё заранее в punycode во избежании путаницы

		return curl_setopt($this->_curl, CURLOPT_URL, $this->_url);
	}

	/**
	 * Выполняем GET-запрос
	 *
	 * @return array данные по ответу
	 */
	protected function _get() {
		return $this->_process();
	}

	/**
	 * Выполняем POST-запрос
	 *
	 * @param array $data данные для post-а
	 * @param bool $encode кодирование входных данных
	 * @return array данные по ответу
	 */
	protected function _post($data, $encode) {
		// ставим нужные опции запроса
		curl_setopt($this->_curl, CURLOPT_POST, true);
		if ($encode) {
			$data = http_build_query($data);
		}

		curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $data);

		$response = $this->_process();

		// откатываем
		curl_setopt($this->_curl, CURLOPT_POST, false);

		return $response;
	}

	/**
	 * Выполняет PUT-запрос
	 *
	 * @param array $data
	 * @param bool $encode кодирование входных данных
	 * @return array
	 */
	protected function _put($data, $encode) {
		if (is_array($data) && $encode) {
			$data = http_build_query($data);
		}

		curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $data);

		return $this->_process();
	}
	
	/**
	 * Выполняем DELETE-запрос
	 *
	 * @return array данные по ответу
	 */
	protected function _delete() {
		curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		
		return $this->_process();
	}
	
	/**
	 * Обрабатываем запрос и ответ
	 * @param string $responce ответ
	 * @param array $headers заголовки
	 * @return array полностью сформировнный ответ
	 */
	protected function _process() {
		foreach (['gzip,deflate', 'deflate,giz', ''] as $encoding) {
			curl_setopt($this->_curl, CURLOPT_ENCODING, $encoding);
			$content = curl_exec($this->_curl);
			if (!empty($content)) {
				break;
			}
		}
		$response = ['content' => $content];

		$headers = curl_getinfo($this->_curl);

		if ($headers['http_code'] != 200) {
			if ($headers['http_code'] > 0) {
				$response['error'] =  $this->_url . ' Неверный код ответа: ' . $headers['http_code'];
			} else {
				$response['error'] = $this->_url . ' ' . curl_error($this->_curl);
			}
		}
		
		$response['headers'] = $headers;
		
		if (empty($response['content']) && !empty($response['error'])) {
			$response['content'] = $response['error'];
		}

		return $response;
	}
}