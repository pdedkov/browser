<?php
namespace Browser\Mixin;

class Tidy extends Base {
	protected $_name = 'tidy';

	/**
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Mixin/Browser\Mixin.Iface::after()
	 */
	public function after(\stdClass $Object, array $params = [], array $arguments = []) {
		if (class_exists('\tidy')) {
			$Tidy = new \tidy();
			$params['content'] = $Tidy->repairString($params['content'], ['output-xhtml' => true], 'utf8');
			unset($Tidy);
		}

		// возвращаем обработанные данные
		return $params;
	}
}