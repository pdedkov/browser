<?php
namespace Browser\Curl\Configurator;

class Exception extends \Exception {
	const INVALID_CONFIGURATOR = 1;
	const INVALID_ADDRESS = 2;
	const INVALID_OPTIONS = 4;
}
