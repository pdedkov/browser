<?php
namespace Browser\Mixin;

abstract class Base extends \Config\Object implements Iface {
	protected $_name = null;

	/**
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Mixin/Browser\Mixin.Iface::name()
	 */
	public function name() {
		return $this->_name;
	}

	/**
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Mixin/Browser\Mixin.Iface::before()
	 */
	public function before(\stdClass $Object, array $params = []) {
		return $params;
	}

	/**
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Mixin/Browser\Mixin.Iface::after()
	 */
	public function after(\stdClass $Object, array $params = [], array $arguments = []) {
		return $params;
	}
}