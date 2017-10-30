<?php
namespace Browser\Mixin;

if (CAKE):
	\App::uses('Xml', 'Utility');
	class Parser extends \Xml {

	};
else:
	class Parser extends \stdClass {
		public static function toArray($xml) {
			return @json_decode(@json_encode($xml), true);
		}
	};
endif;

class Xml extends Base {
	protected $_name = 'xml';

	/**
	 * @param \stdClass $Object
	 * @param array $params
	 * @param array $arguments
	 * (non-PHPdoc)
	 * @see app/Lib/Browser/Mixin/Browser\Mixin.Iface::after()
	 *
	 * @return array
	 */
	public function after(\stdClass $Object, array $params = [], array $arguments = []) {
		try {
			libxml_use_internal_errors(true);

			$xml = simplexml_load_string($params['content']);
			$params['content'] = Parser::toArray($xml);

			// возвращаем обработанные данные
			return $params;
		} catch (\Exception $e) {
			return @json_decode(@json_encode($params['content']), true);
		}

	}
}