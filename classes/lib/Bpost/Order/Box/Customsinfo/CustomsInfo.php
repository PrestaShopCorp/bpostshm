<?php
/**
 * bPost CustomsInfo class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostBpostOrderBoxCustomsinfoCustomsInfo
{
	/**
	 * @var int
	 */
	private $parcel_value;

	/**
	 * @var string
	 */
	private $content_description;

	/**
	 * @var string
	 */
	private $shipment_type;

	/**
	 * @var string
	 */
	private $parcel_return_instructions;

	/**
	 * @var bool
	 */
	private $private_address;

	/**
	 * @param string $content_description
	 * @throws TijsVerkoyenBpostException
	 */
	public function setContentDescription($content_description)
	{
		$length = 50;
		if (mb_strlen($content_description) > $length)
			throw new TijsVerkoyenBpostException(sprintf('Invalid length, maximum is %1$s.', $length));

		$this->content_description = $content_description;
	}

	/**
	 * @return string
	 */
	public function getContentDescription()
	{
		return $this->content_description;
	}

	/**
	 * @param string $parcel_return_instructions
	 * @throws TijsVerkoyenBpostException
	 */
	public function setParcelReturninstructions($parcel_return_instructions)
	{
		$parcel_return_instructions = \Tools::strtoupper($parcel_return_instructions);

		if (!in_array($parcel_return_instructions, self::getPossibleParcelReturnInstructionValues()))
			throw new TijsVerkoyenBpostException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleParcelReturnInstructionValues())
				)
			);

		$this->parcel_return_instructions = $parcel_return_instructions;
	}

	/**
	 * @return string
	 */
	public function getParcelReturninstructions()
	{
		return $this->parcel_return_instructions;
	}

	/**
	 * @return array
	 */
	public static function getPossibleParcelReturnInstructionValues()
	{
		return array(
			'RTA',
			'RTS',
			'ABANDONED',
		);
	}

	/**
	 * @param int $parcel_value
	 */
	public function setParcelValue($parcel_value)
	{
		$this->parcel_value = $parcel_value;
	}

	/**
	 * @return int
	 */
	public function getParcelValue()
	{
		return $this->parcel_value;
	}

	/**
	 * @param boolean $private_address
	 */
	public function setPrivateAddress($private_address)
	{
		$this->private_address = $private_address;
	}

	/**
	 * @return boolean
	 */
	public function getPrivateAddress()
	{
		return $this->private_address;
	}

	/**
	 * @param string $shipment_type
	 * @throws TijsVerkoyenBpostException
	 */
	public function setShipmentType($shipment_type)
	{
		$shipment_type = \Tools::strtoupper($shipment_type);

		if (!in_array($shipment_type, self::getPossibleShipmentTypeValues()))
			throw new TijsVerkoyenBpostException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleShipmentTypeValues())
				)
			);

		$this->shipment_type = $shipment_type;
	}

	/**
	 * @return string
	 */
	public function getShipmentType()
	{
		return $this->shipment_type;
	}

	/**
	 * @return array
	 */
	public static function getPossibleShipmentTypeValues()
	{
		return array(
			'SAMPLE',
			'GIFT',
			'DOCUMENTS',
			'OTHER',
		);
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DomDocument $document
	 * @param  string	   $prefix
	 * @return \DomElement
	 */
	public function toXML(\DOMDocument $document, $prefix = null)
	{
		$tag_name = 'customsInfo';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$customs_info = $document->createElement($tag_name);

		if ($this->getParcelValue() !== null)
		{
			$tag_name = 'parcelValue';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getParcelValue()
				)
			);
		}
		if ($this->getContentDescription() !== null)
		{
			$tag_name = 'contentDescription';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getContentDescription()
				)
			);
		}
		if ($this->getShipmentType() !== null)
		{
			$tag_name = 'shipmentType';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getShipmentType()
				)
			);
		}
		if ($this->getPossibleParcelReturnInstructionValues() !== null)
		{
			$tag_name = 'parcelReturnInstructions';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getParcelReturninstructions()
				)
			);
		}
		if ($this->getPrivateAddress() !== null)
		{
			$tag_name = 'privateAddress';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			if ($this->getPrivateAddress())
				$value = 'true';
			else
				$value = 'false';
			$customs_info->appendChild(
				$document->createElement(
					$tag_name,
					$value
				)
			);
		}

		return $customs_info;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return TijsVerkoyenBpostBpostOrderBoxCustomsinfoCustomsInfo
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$customs_info = new TijsVerkoyenBpostBpostOrderBoxCustomsinfoCustomsInfo();

		if (isset($xml->parcelVlue) && $xml->parcelValue != '')
			$customs_info->setParcelValue(	(int)$xml->parcelValue);
		if (isset($xml->contentDescription) && $xml->contentDescription != '')
			$customs_info->setContentDescription((string)$xml->contentDescription);
		if (isset($xml->shipmentType) && $xml->shipmentType != '')
			$customs_info->setShipmentType((string)$xml->shipmentType);
		if (isset($xml->parcelReturnInstructions) && $xml->parcelReturnInstructions != '')
			$customs_info->setParcelReturninstructions(	(string)$xml->parcelReturnInstructions);
		if (isset($xml->privateAddress) && $xml->privateAddress != '')
			$customs_info->setPrivateAddress(((string)$xml->privateAddress == 'true'));

		return $customs_info;
	}
}
