<?php
/**
 * bPost Sender class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostBpostOrderSender extends TijsVerkoyenBpostBpostOrderCustomer
{
	const TAG_NAME = 'sender';

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return TijsVerkoyenBpostBpostOrderSender
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$sender = new TijsVerkoyenBpostBpostOrderSender();
		$sender = parent::createFromXMLHelper($xml, $sender);

		return $sender;
	}
}
