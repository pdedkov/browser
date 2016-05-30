<?php
// браузер по умолчанию
Configure::write('Browser.agent', 'Browser Agent');

// глобальные настройки конфигуратора
Configure::write('Browser.Curl.Configurator.disabled', ['ip', 'proxy']);