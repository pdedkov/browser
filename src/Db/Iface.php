<?php
namespace Browser\Db;

interface Iface {
	/**
	 * Пишем в кэш
	 * @param string $key ключик
	 * @param string|int $value значение
	 * @param int $timeout время жизни
	 *
	 * @return bool
	 */
	public function writeCache($key, $value, $timeout = null);

	/**
	 * Читаем из кэша
	 * @param string $key ключик
	 *
	 * @return string
	 */
	public function readCache($key);

	/**
	 * Время последнего обращения к хосту
	 * @param string $domain хост
	 * @param int $timeout устанавливаем timeout
	 *
	 * @return mixed
	 */
	public function timeout($domain, $timeout = null);

	/**
	 *
	 * @param string $domain домен
	 * @param int $time время жизни
	 *
	 * @return bool
	 */
	public function request($domain, $time = null);
}