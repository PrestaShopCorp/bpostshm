<?php
/**
 * 2014 Stigmi
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Carrier extends CarrierCore
{
	public function __construct($id = null, $id_lang = null)
	{
		if (_PS_VERSION_ > 1.4)
		{
			$this->fieldsValidate['delay'] = 'isCleanHtml';
			$this->fieldsSizeLang['delay'] = 255;
		}

		parent::__construct($id, $id_lang);
	}

	/**
	 * @param int|null $id
	 * @param int|null $id_lang
	 */

	/**
	 * Retrieve carriers position
	 *
	 * @param array $id_carriers
	 * @param bool $active
	 * @return array
	 */
	public static function sortIdCarriersByPosition($id_carriers = array(), $active = true)
	{
		$ret = array();
		$query = '
SELECT
	id_carrier
FROM
	`'._DB_PREFIX_.'carrier`'
.(
	(!empty($id_carriers) && is_array($id_carriers)) || $active ? ' WHERE ' : ''
)
.(!empty($id_carriers) && is_array($id_carriers) ? '
	id_carrier IN ('.implode(', ', $id_carriers).')'.($active ? ' AND' : '') : ''
)
.($active ? '
	active = 1
AND
	deleted = 0' : '').'
ORDER BY
	position';
		$result = Db::getInstance()->executeS($query);
		if (!empty($result) && is_array($result))
		{
			foreach ($result as $row)
				$ret[] = (int)$row['id_carrier'];
		}
		return $ret;
	}
}