<?php
/**
 * bPost Receiver class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderReceiver extends EontechModBpostOrderCustomer
{
	const TAG_NAME = 'receiver';

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return EontechModBpostOrderReceiver
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$receiver = new EontechModBpostOrderReceiver();
		$receiver = parent::createFromXMLHelper($xml, $receiver);

		return $receiver;
	}
}
