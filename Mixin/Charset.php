<?php
namespace Browser\Mixin;

use Charset\Detector;

class Charset extends Base {
	protected $_name = 'charset';

	protected static $_defaults = [
		'charset' => 'utf-8',
		'countUtf' => 100
	];

	/**
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Mixin/Browser\Mixin.Iface::after()
	 */
	public function after(\stdClass $Object, array $params = [], array $arguments = []) {
		// пытаемся определить кодировку текста
		$charsets = $this->_detectCharset($params['content'], $params['headers'], false);

		if (in_array('utf-8', $charsets)) {
			$charset = 'utf-8';
		} else {
			$charset = $charsets[0];
		}

		if (is_array($charset)) {
			$replace = $charset[1];
			$charset = $charset[0];
		} else {
			$replace = $charset;
		}

		if ($charset != $this->_config['charset']) {
			$page = @mb_convert_encoding($params['content'], $this->_config['charset'], $charset);
		} else {
			$page = $params['content'];
		}

		// определилил как utf, но это нифига не оно
		if (preg_match('/Р.Р.Р.Р/u', $page) > $this->_config['countUtf']) {
			$page = $params['content'];
		}

		// довольно глупый хак для phpquery, иногда он детектит кодировку сам all-rammstein.ru, иногда не выходит у него
		// www.siblux.ru поэтому просто заменяет в теле текст
		$params['content'] = str_ireplace(
			array("charset={$replace}", "charset=" . mb_strtoupper($replace)),
			"charset=" . $this->_config['charset'],
			$page
		);

		// возвращаем обработанные данные
		return $params;
	}

	/**
	 * Определение кодировки по мета-тегу в HTML или по Content-type в заголовке.
	 * Если не удалось определить, вернет пустую строку.
	 *
	 * @param string $content содержимое
	 * @param string $headers полученные заголовки
	 * @param bool $single все найденные значения
	 * @return string
	 */
	protected function _detectCharset($content, $headers, $single = true) {
		ini_set('pcre.backtrack_limit', 1000000000);

		$CharsetDetector = new Detector();

		// массив найденных кодировок
		$detected = [];

		$charset = null;

		$regexp = "/<meta.+?charset\s*=\s*([\w\s\d-]*)[\"|']/is";
		preg_match($regexp, $content, $matches);
		if (isset($matches[1]) && strlen($matches[1])) {
			$found = $CharsetDetector->normalize($matches[1]);
			if ($single) {
				return $found;
			} else {
				$detected[] = $found;
			}
		}

		if (empty($detected)) {
			$regexp = "/charset.+?=([\w\s\d-]+?)[\"|']/is";
			preg_match($regexp, $content, $matches);
			if (!empty($matches[1])) {
				$matches[1] = trim($matches[1]);
				if ($matches[1]) {
					$found = $CharsetDetector->normalize($matches[1]);
					if (!empty($found)) {
						if ($single) {
							return $found;
						} else {
							$detected[] = $found;
						}
					}

				}
			}
		}

		if (!empty($headers) && is_array($headers)) {
		    foreach ($headers as $name => $header) {
		    	if ($name == 'content_type') {
		        	preg_match('/([\w\+]+);\s+charset=([\w\s\d-]*)/is', $header, $matches);
		        	if (isset($matches[2])) {
						$found = $CharsetDetector->normalize(strtolower($matches[2]));
		          		if ($single) {
							return $found;
						} else {
							$detected[] = $found;
						}
		        	}
		      	}
		    }
	    }

		// есть уникумы, которые ввообще не в курсе, как оформляются теги
		$regexp = "/<meta.+?charset\s*=\s*([\w\s\d-]*)/is";
		preg_match($regexp, $content, $matches);
		if (isset($matches[1]) && strlen($matches[1])) {
			$found = $CharsetDetector->normalize($matches[1]);
			if ($single) {
				return $found;
			} else {
				$detected[] = $found;
			}

		}

		//экспресс-проверка на utf-8
		if (preg_match('#.#u', $content) > 0) {
			if ($single) {
				return 'utf-8';
			} else {
				$detected[] = 'utf-8';
			}
		}

		if ($single || empty($detected)) {
			throw new Exception('Не удалось определить кодировку', Exception::WRONG_DATA);
		}

		return $detected;
	}
}