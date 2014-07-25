<?php
/**
 * bPost Sender class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

namespace TijsVerkoyen\Bpost\Bpost\Order;

class Sender extends Customer
{
	const TAG_NAME = 'sender';

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return Sender
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$sender = new Sender();
		$sender = parent::createFromXMLHelper($xml, $sender);

		return $sender;
	}
}
