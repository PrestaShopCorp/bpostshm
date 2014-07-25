<?php
/**
 * bPost National class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

namespace TijsVerkoyen\Bpost\Bpost\Order\Box;

abstract class National
{
	/**
	 * @var string
	 */
	protected $product;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @var int
	 */
	protected $weight;

	/**
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @param \TijsVerkoyen\Bpost\Bpost\Order\Box\Option\Option $option
	 */
	public function addOption(Option\Option $option)
	{
		$this->options[] = $option;
	}

	/**
	 * @param string $product
	 */
	public function setProduct($product)
	{
		$this->product = $product;
	}

	/**
	 * @return string
	 */
	public function getProduct()
	{
		return $this->product;
	}

	/**
	 * @remark should be implemented by the child class
	 * @return array
	 */
	public static function getPossibleProductValues()
	{
		return array();
	}

	/**
	 * @param int $weight
	 */
	public function setWeight($weight)
	{
		$this->weight = $weight;
	}

	/**
	 * @return int
	 */
	public function getWeight()
	{
		return $this->weight;
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DomDocument $document
	 * @param  string	   $prefix
	 * @param  string	   $type
	 * @return \DomElement
	 */
	public function toXML(\DOMDocument $document, $prefix = null, $type = null)
	{
		$type_element = $document->createElement($type);

		if ($this->getProduct() !== null)
		{
			$tag_name = 'product';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$type_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getProduct()
				)
			);
		}

		$options = $this->getOptions();
		if (!empty($options))
		{
			$options_element = $document->createElement('options');
			foreach ($options as $option)
				$options_element->appendChild($option->toXML($document));
			$type_element->appendChild($options_element);
		}

		if ($this->getWeight() !== null)
		{
			$tag_name = 'weight';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$type_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getWeight()
				)
			);
		}

		return $type_element;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{

	}
}
