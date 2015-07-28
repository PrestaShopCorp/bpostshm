<?php
/**
 * 2015 Stigmi
 *
 * bpost Shipping Manager
 *
 * Allow your customers to choose their preferrred delivery method: delivery at home or the office, at a pick-up location or in a bpack 24/7 parcel
 * machine.
 *
 * @author    Serge <serge@stigmi.eu>
 * @copyright 2015 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

/*  object module ($this) available */
function upgrade_module_1_22_0($object)
{
	$upgrade_version = '1.22.0';
	$object->upgrade_detail[$upgrade_version] = array();
	$return = true;

	/* db changes */
	$sql = '
ALTER TABLE `'._DB_PREFIX_.'cart_bpost`
ADD COLUMN `upl_info` TEXT AFTER `option_kmi`';

	if (!Db::getInstance()->execute($sql))
		$object->upgrade_detail[$upgrade_version][] = $object->l('Can\'t alter bpost cart table');

	$return = $return && empty($object->upgrade_detail[$upgrade_version]);
	$return = $return && Configuration::updateGlobalValue('BPOST_ACCOUNT_API_URL', $object::API_URL);

	$return = $return && $object->upgradeTo($upgrade_version);

	return $return;
}