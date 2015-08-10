<?php
/**
 * BpostUPLInfo class
 *
 * @author    Serge <serge@stigmi.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Eontech.net All rights reserved.
 * @license   BSD License
 */

class EontechBpostUPLInfo extends EontechModBpostOrderUnregisteredInfo
{
	protected static $_map = array(
		'lng' => 'setLanguage',
		'eml' => 'setEmailAddress',
		'mob' => 'setMobilePhone',
		'rmz' => 'setReducedMobilityZone',
		);

	public static function createFromJson($info = '')
	{
		$info = Tools::jsonDecode($info, true);

		$upl_info = new self();
		foreach (self::$_map as $key => $func)
			if (isset($info[$key]) && !empty($info[$key]))
				$upl_info->$func($info[$key]);

		return $upl_info;
	}
}