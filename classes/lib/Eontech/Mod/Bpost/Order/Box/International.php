<?php
/**
 * bPost International class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @author  Serge <serge@stigmi.eu>
 *          Needed to modify this to include 'bpack World Easy Return'
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderBoxInternational
{
	/**
	 * @var string
	 */
	private $product;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var EontechModBpostOrderReceiver
	 */
	private $receiver;

	/**
	 * @var int
	 */
	private $parcel_weight;

	/**
	 * @var EontechModBpostOrderBoxCustomsinfoCustomsInfo
	 */
	private $customs_info;

	/**
	 * @param EontechModBpostOrderBoxCustomsinfoCustomsInfo $customs_info
	 */
	public function setCustomsInfo($customs_info)
	{
		$this->customs_info = $customs_info;
	}

	/**
	 * @return EontechModBpostOrderBoxCustomsinfoCustomsInfo
	 */
	public function getCustomsInfo()
	{
		return $this->customs_info;
	}

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
	 * @param EontechModBpostOrderBoxOption $option
	 */
	public function addOption(EontechModBpostOrderBoxOption $option)
	{
		$this->options[] = $option;
	}

	/**
	 * @param int $parcel_weight
	 */
	public function setParcelWeight($parcel_weight)
	{
		$this->parcel_weight = $parcel_weight;
	}

	/**
	 * @return int
	 */
	public function getParcelWeight()
	{
		return $this->parcel_weight;
	}

	/**
	 * @param string $product
	 * @throws EontechModException
	 */
	public function setProduct($product)
	{
		if (!in_array($product, self::getPossibleProductValues()))
			throw new EontechModException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleProductValues())
				)
			);

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
	 * @return array
	 */
	public static function getPossibleProductValues()
	{
		return array(
			'bpack World Business',
			'bpack World Express Pro',
			'bpack Europe Business',
			'bpack World Easy Return',
		);
	}

	/**
	 * @param EontechModBpostOrderReceiver $receiver
	 */
	public function setReceiver($receiver)
	{
		$this->receiver = $receiver;
	}

	/**
	 * @return EontechModBpostOrderReceiver
	 */
	public function getReceiver()
	{
		return $this->receiver;
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
		$tag_name = 'internationalBox';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$international_box = $document->createElement($tag_name);
		$international = $document->createElement('international:international');
		$international_box->appendChild($international);

		if ($this->getProduct() !== null)
			$international->appendChild(
				$document->createElement(
					'international:product',
					$this->getProduct()
				)
			);

		$options = $this->getOptions();
		if (!empty($options))
		{
			$options_element = $document->createElement('international:options');
			foreach ($options as $option)
				$options_element->appendChild($option->toXML($document));
			$international->appendChild($options_element);
		}

		if ($this->getReceiver() !== null)
			$international->appendChild($this->getReceiver()->toXML($document, 'international'));

		if ($this->getParcelWeight() !== null)
			$international->appendChild(
				$document->createElement(
					'international:parcelWeight',
					$this->getParcelWeight()
				)
			);

		if ($this->getCustomsInfo() !== null)
			$international->appendChild($this->getCustomsInfo()->toXML($document, 'international'));

		return $international_box;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return EontechModBpostOrderBoxInternational
	 * @throws EontechModException
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$international = new EontechModBpostOrderBoxInternational();

		if (isset($xml->international->product) && $xml->international->product != '')
			$international->setProduct((string)$xml->international->product);
		if (isset($xml->international->options))
			foreach ($xml->international->options as $option_data)
			{
				$option_data = $option_data->children('http://schema.post.be/shm/deepintegration/v3/common');

				if (in_array($option_data->getName(), array('infoDistributed')))
					$option = EontechModBpostOrderBoxOptionMessaging::createFromXML($option_data);
				else
				{
					$class_name = 'EontechModBpostOrderBoxOption'.\Tools::ucfirst($option_data->getName());
					if (!method_exists($class_name, 'createFromXML'))
						throw new EontechModException('Not Implemented');
					$option = call_user_func(
						array($class_name, 'createFromXML'),
						$option_data
					);
				}

				$international->addOption($option);
			}
		if (isset($xml->international->parcelWeight) && $xml->international->parcelWeight != '')
			$international->setParcelWeight((int)$xml->international->parcelWeight);
		if (isset($xml->international->receiver))
		{
			$receiver_data = $xml->international->receiver->children(
				'http://schema.post.be/shm/deepintegration/v3/common'
			);
			$international->setReceiver(
				EontechModBpostOrderReceiver::createFromXML($receiver_data)
			);
		}
		if (isset($xml->international->customsInfo))
			$international->setCustomsInfo(EontechModBpostOrderBoxCustomsinfoCustomsInfo::createFromXML($xml->international->customsInfo));

		return $international;
	}
}
