<?php
/**
 * bPost Address class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostBpostOrderAddress
{
	const TAG_NAME = 'common:address';

	/**
	 * @var string
	 */
	private $street_name;

	/**
	 * @var string
	 */
	private $number;

	/**
	 * @var string
	 */
	private $box;

	/**
	 * @var string
	 */
	private $postal_code;

	/**
	 * @var string
	 */
	private $locality;

	/**
	 * @var string
	 */
	private $country_code = 'BE';

	/**
	 * @param string $box
	 * @throws TijsVerkoyenBpostException
	 */
	public function setBox($box)
	{
		$length = 8;
		if (mb_strlen($box) > $length)
			throw new TijsVerkoyenBpostException(sprintf('Invalid length, maximum is %1$s.', $length));
		$this->box = $box;
	}

	/**
	 * @return string
	 */
	public function getBox()
	{
		return $this->box;
	}

	/**
	 * @param string $country_code
	 * @throws TijsVerkoyenBpostException
	 */
	public function setCountryCode($country_code)
	{
		$length = 2;
		if (mb_strlen($country_code) > $length)
			throw new TijsVerkoyenBpostException(sprintf('Invalid length, maximum is %1$s.', $length));
		$this->country_code = \Tools::strtoupper($country_code);
	}

	/**
	 * @return string
	 */
	public function getCountryCode()
	{
		return $this->country_code;
	}

	/**
	 * @param string $locality
	 * @throws TijsVerkoyenBpostException
	 */
	public function setLocality($locality)
	{
		$length = 40;
		if (mb_strlen($locality) > $length)
			throw new TijsVerkoyenBpostException(sprintf('Invalid length, maximum is %1$s.', $length));
		$this->locality = $locality;
	}

	/**
	 * @return string
	 */
	public function getLocality()
	{
		return $this->locality;
	}

	/**
	 * @param string $number
	 * @throws TijsVerkoyenBpostException
	 */
	public function setNumber($number)
	{
		$length = 8;
		if (mb_strlen($number) > $length)
			throw new TijsVerkoyenBpostException(sprintf('Invalid length, maximum is %1$s.', $length));
		$this->number = $number;
	}

	/**
	 * @return string
	 */
	public function getNumber()
	{
		return $this->number;
	}

	/**
	 * @param string $postal_code
	 * @throws TijsVerkoyenBpostException
	 */
	public function setPostalCode($postal_code)
	{
		$length = 40;
		if (mb_strlen($postal_code) > $length)
			throw new TijsVerkoyenBpostException(sprintf('Invalid length, maximum is %1$s.', $length));

		$this->postal_code = $postal_code;
	}

	/**
	 * @return string
	 */
	public function getPostalCode()
	{
		return $this->postal_code;
	}

	/**
	 * @param string $street_name
	 * @throws TijsVerkoyenBpostException
	 */
	public function setStreetName($street_name)
	{
		$length = 40;
		if (mb_strlen($street_name) > $length)
			throw new TijsVerkoyenBpostException(sprintf('Invalid length, maximum is %1$s.', $length));
		$this->street_name = $street_name;
	}

	/**
	 * @return string
	 */
	public function getStreetName()
	{
		return $this->street_name;
	}

	/**
	 * @param string $street_name
	 * @param string $number
	 * @param string $box
	 * @param string $postal_code
	 * @param string $locality
	 * @param string $country_code
	 */
	public function __construct($street_name = null, $number = null, $box = null, $postal_code = null, $locality = null, $country_code = null)
	{
		if ($street_name != null)
			$this->setStreetName($street_name);
		if ($number != null)
			$this->setNumber($number);
		if ($box != null)
			$this->setBox($box);
		if ($postal_code != null)
			$this->setPostalCode($postal_code);
		if ($locality != null)
			$this->setLocality($locality);
		if ($country_code != null)
			$this->setCountryCode($country_code);
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DOMDocument $document
	 * @param  string	   $prefix
	 * @return \DOMElement
	 */
	public function toXML(\DOMDocument $document, $prefix = 'common')
	{
		$tag_name = static::TAG_NAME;
		$address = $document->createElement($tag_name);
		$document->appendChild($address);

		if ($this->getStreetName() !== null)
		{
			$tag_name = 'streetName';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$address->appendChild(
				$document->createElement(
					$tag_name,
					$this->getStreetName()
				)
			);
		}
		if ($this->getNumber() !== null)
		{
			$tag_name = 'number';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$address->appendChild(
				$document->createElement(
					$tag_name,
					$this->getNumber()
				)
			);
		}
		if ($this->getBox() !== null)
		{
			$tag_name = 'box';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$address->appendChild(
				$document->createElement(
					$tag_name,
					$this->getBox()
				)
			);
		}
		if ($this->getPostalCode() !== null)
		{
			$tag_name = 'postalCode';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$address->appendChild(
				$document->createElement(
					$tag_name,
					$this->getPostalCode()
				)
			);
		}
		if ($this->getLocality() !== null)
		{
			$tag_name = 'locality';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$address->appendChild(
				$document->createElement(
					$tag_name,
					$this->getLocality()
				)
			);
		}
		if ($this->getCountryCode() !== null)
		{
			$tag_name = 'countryCode';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$address->appendChild(
				$document->createElement(
					$tag_name,
					$this->getCountryCode()
				)
			);
		}

		return $address;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return TijsVerkoyenBpostBpostOrderAddress
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$address = new TijsVerkoyenBpostBpostOrderAddress();

		if (isset($xml->streetName) && $xml->streetName != '')
			$address->setStreetName((string)$xml->streetName);
		if (isset($xml->number) && $xml->number != '')
			$address->setNumber((string)$xml->number);
		if (isset($xml->box) && $xml->box != '')
			$address->setBox((string)$xml->box);
		if (isset($xml->postalCode) && $xml->postalCode != '')
			$address->setPostalCode((string)$xml->postalCode);
		if (isset($xml->locality) && $xml->locality != '')
			$address->setLocality((string)$xml->locality);
		if (isset($xml->countryCode) && $xml->countryCode != '')
			$address->setCountryCode((string)$xml->countryCode);

		return $address;
	}
}
