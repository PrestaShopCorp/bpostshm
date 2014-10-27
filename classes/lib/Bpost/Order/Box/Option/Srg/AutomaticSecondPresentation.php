<?php
/**
 * bPost AutomaticSecondPresentation class
 *
 * @author    Serge Jamasb <serge@stigmi.eu>
 * @author 	  Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostBpostOrderBoxOptionSrgAutomaticSecondPresentation extends TijsVerkoyenBpostBpostOrderBoxOption
{
	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DomDocument $document
	 * @param  string	   $prefix
	 * @return \DomElement
	 */
	public function toXML(\DOMDocument $document, $prefix = 'common')
	{
		$tag_name = 'automaticSecondPresentation';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		return $document->createElement($tag_name);
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return AutomaticSecondPresentation
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		return new TijsVerkoyenBpostBpostOrderBoxOptionSrgAutomaticSecondPresentation();
	}
}
