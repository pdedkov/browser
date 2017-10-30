<?php
namespace Browser\Mixin;
//1. before - модифицирует аргументы для вызова метода
//2. вызывается метод с модифицированными аргументами
//3. after - модифицирует результат, вызывается с результатом и первоначальными аргументами

/**
 * Интерфейсе для миксов
 *
 * @author pavel
 */
interface Iface {
	/**
	 * Выполняем перед началом обработки, модифицирует первоначальные аргументы для вызова метода
	 *
	 * @param object объект
	 * @param array $params
	 * @return array $modifiedParams
	 */
	public function before(\stdClass $Object, array $params = []);

	/**
	 * Выполняем после обработки с первоначальными аргументами, модифицирует результат
	 *
	 * @param object $object объект
	 * @param array $params результат
	 * @param array $arguments первоначальные аргументы
	 * @return array $result
	 */
	public function after(\stdClass $Object, array $params = [], array $arguments = []);

	/**
	 * Возвращаем текущее имя mix-а
	 *
	 * @return string
	 */
	public function name();
}