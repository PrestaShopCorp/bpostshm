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
function upgrade_module_1_21_0($object)
{
	$upgrade_version = '1.21.0';
	$object->upgrade_detail[$upgrade_version] = array();

	$new_hooks = array(
			'orderDetailDisplayed',		// displayOrderDetail
		);

	/*  Try to register the new hook since last version */
	foreach ($new_hooks as $hook)
			if (!$object->isRegisteredInHook($hook))
				$object->registerHook($hook);

	return (bool)count($object->upgrade_detail[$upgrade_version]);
}