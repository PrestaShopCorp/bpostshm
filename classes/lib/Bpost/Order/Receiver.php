<?php
/**
 * bPost Receiver class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostBpostOrderReceiver extends TijsVerkoyenBpostBpostOrderCustomer
{
	const TAG_NAME = 'receiver';

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return TijsVerkoyenBpostBpostOrderReceiver
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$receiver = new TijsVerkoyenBpostBpostOrderReceiver();
		$receiver = parent::createFromXMLHelper($xml, $receiver);

		return $receiver;
	}
}
