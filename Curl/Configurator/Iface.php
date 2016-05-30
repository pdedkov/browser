<?php
namespace Browser\Curl\Configurator;

interface Iface {
	/**
	 * Конфигурируем url
	 * @param resource $handler curl-handler
	 * @param array $options опции конфигурации
	 */
	public function configure(&$handler, $options);
}