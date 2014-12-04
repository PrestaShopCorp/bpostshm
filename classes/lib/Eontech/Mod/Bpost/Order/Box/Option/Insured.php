<?php
/**
 * bPost Insurance class
 *
 * @author    Serge Jamasb <serge@stigmi.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Eontech.net. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderBoxOptionInsured extends EontechModBpostOrderBoxOption
{
	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $value;

	/**
	 * @return array
	 */
	public static function getPossibleTypeValues()
	{
		return array(
			'basicInsurance',
			'additionalInsurance',
		);
	}

	/**
	 * @param string $type
	 * @throws EontechModException
	 */
	public function setType($type)
	{
		if (!in_array($type, self::getPossibleTypeValues()))
			throw new EontechModException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleTypeValues())
				)
			);

		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $value
	 * @throws EontechModException
	 */
	public function setValue($value)
	{
		if (!in_array($value, self::getPossibleValueValues()))
			throw new EontechModException(
				sprintf(
					'Invalid value "'.$value.'", possible values are: %1$s.',
					implode(', ', self::getPossibleValueValues())
				)
			);

		$this->value = $value;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @return array
	 */
	public static function getPossibleValueValues()
	{
		return array(
			// 1,	// appears to be valid too
			2,
			3,
			4,
			5,
			6,
			7,
			8,
			9,
			10,
			11
		);
	}

	/**
	 * @param string	  $type
	 * @param null|string $value
	 */
	public function __construct($type, $value = null)
	{
		$this->setType($type);
		if ($value !== null)
			$this->setValue($value);
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DomDocument $document
	 * @param  string	   $prefix
	 * @return \DomElement
	 */
	/* public function toXML(\DOMDocument $document, $prefix = null) */
	public function toXML(\DOMDocument $document, $prefix = 'common')
	{
		$tag_name = 'insured';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;
		$insured = $document->createElement($tag_name);

		$tag_name = $this->getType();
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;
		$insurance = $document->createElement($tag_name);
		$insured->appendChild($insurance);

		if ($this->getValue() !== null)
			$insurance->setAttribute('value', $this->getValue());

		return $insured;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return Insured
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$value = 0;
		if (isset($xml->insured->additionalInsurance))
			$value = (int)$xml->insured->additionalInsurance->attributes()->value;

		if (1 === $value)
			return new EontechModBpostOrderBoxOptionInsured('basicInsurance');
		else
			return new EontechModBpostOrderBoxOptionInsured('additionalInsurance', $value);

	}

}
