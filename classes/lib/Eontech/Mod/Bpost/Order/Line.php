<?php
/**
 * bPost Line class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderLine
{
	/**
	 * @var string
	 */
	private $text;

	/**
	 * @var int
	 */
	private $number_of_items;

	/**
	 * @param int $nb_of_items
	 */
	public function setNumberOfitems($nb_of_items)
	{
		$this->number_of_items = $nb_of_items;
	}

	/**
	 * @return int
	 */
	public function getNumberOfitems()
	{
		return $this->number_of_items;
	}

	/**
	 * @param string $text
	 */
	public function setText($text)
	{
		$this->text = $text;
	}

	/**
	 * @return string
	 */
	public function getText()
	{
		return $this->text;
	}

	/**
	 * @param string $text
	 * @param int	$number_of_items
	 */
	public function __construct($text = null, $number_of_items = null)
	{
		if ($text != null)
			$this->setText($text);
		if ($number_of_items != null)
			$this->setNumberOfitems($number_of_items);
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DomDocument $document
	 * @param  string	   $prefix
	 * @return \DOMElement
	 */
	public function toXML(\DomDocument $document, $prefix = null)
	{
		$tag_name = 'orderLine';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$line = $document->createElement($tag_name);

		if ($this->getText() !== null)
		{
			$tag_name = 'text';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$line->appendChild(
				$document->createElement(
					$tag_name,
					$this->getText()
				)
			);
		}
		if ($this->getNumberOfitems() !== null)
		{
			$tag_name = 'nbOfItems';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$line->appendChild(
				$document->createElement(
					$tag_name,
					$this->getNumberOfitems()
				)
			);
		}

		return $line;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return EontechModBpostOrderLine
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$line = new EontechModBpostOrderLine();
		if (isset($xml->text) && $xml->text != '')
			$line->setText((string)$xml->text);
		if (isset($xml->nbOfItems) && $xml->nbOfItems != '')
			$line->setNumberOfitems((int)$xml->nbOfItems);

		return $line;
	}
}
