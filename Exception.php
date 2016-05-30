<?php
namespace Browser;

class Exception extends \Exception {
	const INVALID_MIXIN = 1;
	// ошибки
	// нет данных авторизации
	const UNATHORIZED			= 401;
	// пользователь авторизован, но доступ запрещён
	const FORBIDDEN				= 403;
	// объект не найден
	const NOT_FOUND				= 404;
	// доступные методы ресурса
	const NOT_ALLOWED			= 405;
	// ошибки сервиса
	// внутреняя ошибка сервиса, не ясно что с ним, но что-то не так
	const INTERNAL_ERROR	= 500;
	// метод должен быть, но пока что не реализован
	const NOT_IMPLEMENTED	= 501;
	// сервис в данный момент не доступен (тех. работы, перегрузка и тд)
	const UNAVAILABLE		= 503;
	// timeout
	const TIMEOUT			= 504;
	// не поддержимается работа по выбранному протоколу
	const NOT_SUPPORTED		= 505;
	// такого сайта вообще нет
	const NOT_EXISTS = 0;

	/**
	 * Тексты ошибок
	 * @var []
	 */
	public $messages = [];

	public function __construct($message, $code = self::INTERNAL_ERROR) {
		$this->messages = [
			self::INVALID_MIXIN => 'Внутренняя ошибка сервиса. Обратитесь в службу поддержки',
			self::UNATHORIZED => 'Сайт требует авторизации',
			self::FORBIDDEN => 'Доступ к сайту запрещён',
			self::NOT_FOUND => 'Сайт не отвечает',
			self::NOT_ALLOWED => 'Доступ к сайту ограничен',
			self::INTERNAL_ERROR => 'На сайте произошла ошибка',
			self::NOT_IMPLEMENTED => 'Сайт не отвечает',
			self::UNAVAILABLE => 'Сайт в данный момент не доступен',
			self::TIMEOUT => 'Сайт в данный момент не доступен',
			self::NOT_EXISTS => 'Ошибка подключения. Возможно неверно указан url сайта'
		];

		if (array_key_exists($code, $this->messages)) {
			$message .= ' -> ' . $this->messages[$code];
		} else {
			$message .= ' -> Ошибка доступа к сайту: ' . $code;
		}

		parent::__construct($message, $code);
	}
}
