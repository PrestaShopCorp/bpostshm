<?php
/**
 * bPost Sender class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderSender extends EontechModBpostOrderCustomer
{
	const TAG_NAME = 'sender';

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return EontechModBpostOrderSender
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$sender = new EontechModBpostOrderSender();
		$sender = parent::createFromXMLHelper($xml, $sender);

		return $sender;
	}
}
