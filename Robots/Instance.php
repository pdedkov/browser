<?php
namespace Browser\Robots;

use Uri\Instance as Uri;
use Browser\Instance as Browser;
use Config\Singleton as Base;

class Instance extends Base {
	/**
	 * Базовые настройки
	 * @var array
	 */
	protected static $_defaults = [
		'agent' => 'Robots UA'
	];

	protected static $_instance = null;

	/**
	 * Создает экземпляр объекта данного класса, если еще не был создан
	 *
	 * @param object экземпляр объекта класса
	 */
	public static function getInstance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self(__NAMESPACE__);
		}

		return self::$_instance;
	}

	/**
	 *
	 * Извлекаем ветки директив для разных агентов
	 * @param string $content содержимое файла robot.txt которое будем парсить
	 *
	 * @param array возвращаем ветки для разных агентов
	 */
	protected function _parse($content) {
		$strings = preg_split('#[\n]+#is', $content);
		$currentUserAgent = '';
		// Здесь храним ветки для разных User-Agent
		$branches = [];
		foreach($strings as $line) {
			// Делим строку на директиву - значение
			$line = trim(current(explode('#', trim($line), 2)));
			if (substr_count($line, ':') < 1) {
				continue;
			}
			$line = explode(':', $line, 2);
			$currentDirective = strtolower(trim($line[0]));
			$currentValue = trim($line[1]);
			// Ищем права доступа по веткам для разных User-Agent
			if ($currentDirective == 'user-agent') {
				$currentUserAgent = $currentValue;
			} elseif ($currentUserAgent != '') {
				$branches[$currentUserAgent][] = [
					'agent'		=> $currentUserAgent,
					'directive'	=> $currentDirective,
					'value'		=> $currentValue
				];
			}
		}

		return $branches;
	}

	/**
	 *
	 * Возвращает все доступные директивы для данного агента
	 *
	 * @param string $userAgent агент, для которого ищем директивы
	 * @param array $branches ветки директив для разных агентов
	 * @return mixed возвращаем false если ни чего не найдено, или массив директив и их значений для данного User-agent и *
	 */
	protected function _getDirectiveForAgent($agent, $branches) {
		$agent = strtolower($agent);
		$derectives = [];
		// Проходим по веткам, если они касаются искомого User-agent возвращаем директивы
		foreach($branches as $uaMask => $data) {
			if (($uaMask == '*' || @preg_match('#' . preg_quote($uaMask, '#') . '#is', $agent))) {
				$derectives = array_merge($derectives, $data);
			}
		}

		return $derectives;
	}

	/**
	 *
	 * Проверка доступа $userAgent к $url
	 * @param string $url урл, доступ к которому, нужно проверить
	 * @param string $userAgent агент, для которого нужно проверить доступ
	 *
	 * @return bool true
	 */
	public static function get($url, $directive = 'Crawl-Delay', $agent = null) {
		$_this = self::getInstance();

		$agent = $agent ?: $_this->_config['agent'];

		$value = [];
		try {
			// Получаем содержимое и сразу же извлекает оттуда дерективы для разных UA
			$branches = $_this->_parse($_this->_request($url, $agent));

			// Сортируем массив веток - чтобы правила для искомого User-agent были в начале списка
			krsort($branches, SORT_STRING);

			// Извлекаем директивы для искомого агента и *
			$info = $_this->_getDirectiveForAgent($agent, $branches);

			foreach ($info as $rule) {
				if (in_array($rule['directive'], (array)$directive) && in_array($rule['agent'], [$agent, '*'])) {
					$value[] = $rule;
				}
			}
		} catch(\Browser\Exception $e)  {}

		return $value;
	}

	/**
	 * Загружаем robots txt
	 *
	 * @param string $url домен или url для загрузки
	 * @return string $content содержимое robots
	 */
	protected function _request($url, $agent) {
		Browser::pack();

		Browser::configure(['agent' => $agent], ['direct' => true, 'exception' => true]);
		$content = Browser::get(Uri::getHost($url) . '/robots.txt');

		Browser::unpack();

		return $content;
	}
}