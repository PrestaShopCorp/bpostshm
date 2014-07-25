<?php
/**
 * bPost Receiver class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

namespace TijsVerkoyen\Bpost\Bpost\Order;

class Receiver extends Customer
{
	const TAG_NAME = 'receiver';

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return Receiver
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$receiver = new Receiver();
		$receiver = parent::createFromXMLHelper($xml, $receiver);

		return $receiver;
	}
}
