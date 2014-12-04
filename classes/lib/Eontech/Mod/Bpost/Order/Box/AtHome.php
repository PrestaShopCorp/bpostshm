<?php
/**
 * bPost AtHome class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderBoxAtHome extends EontechModBpostOrderBoxNational
{
	/**
	 * @var array
	 */
	private $opening_hours;

	/**
	 * @var string
	 */
	private $desired_delivery_place;

	/**
	 * @var EontechModBpostOrderReceiver
	 */
	private $receiver;

	/**
	 * @param string $desired_delivery_place
	 */
	public function setDesiredDeliveryplace($desired_delivery_place)
	{
		$this->desired_delivery_place = $desired_delivery_place;
	}

	/**
	 * @return string
	 */
	public function getDesiredDeliveryplace()
	{
		return $this->desired_delivery_place;
	}

	/**
	 * @param array $opening_hours
	 */
	public function setOpeningHours($opening_hours)
	{
		$this->opening_hours = $opening_hours;
	}

	/**
	 * @param EontechModBpostOrderBoxOpeninghourDay $day
	 */
	public function addOpeningHour(EontechModBpostOrderBoxOpeninghourDay $day)
	{
		$this->opening_hours[] = $day;
	}

	/**
	 * @return array
	 */
	public function getOpeningHours()
	{
		return $this->opening_hours;
	}

	/**
	 * @param string $product Possible values are:
	 *						  * bpack 24h Pro,
	 *						  * bpack 24h business
	 *						  * bpack Bus
	 *						  * bpack Pallet
	 *						  * bpack Easy Retour
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

		parent::setProduct($product);
	}

	/**
	 * @return array
	 */
	public static function getPossibleProductValues()
	{
		return array(
			'bpack 24h Pro',
			'bpack 24h business',
			'bpack Bus',
			'bpack Pallet',
			'bpack Easy Retour',
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
	 * @param  string	   $prefix
	 * @return \DomElement
	 */
	public function toXML(\DOMDocument $document, $prefix = null, $type = null)
	{
		$tag_name = 'nationalBox';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;
		$national_element = $document->createElement($tag_name);
		$box_element = parent::toXML($document, null, 'atHome');
		$national_element->appendChild($box_element);

		$opening_hours = $this->getOpeningHours();
		if (!empty($opening_hours))
		{
			$opening_hours_element = $document->createElement('openingHours');
			foreach ($opening_hours as $day)
				/** @var $day EontechModBpostOrderBoxOpeninghourDay */
				$opening_hours_element->appendChild($day->toXML($document));
			$box_element->appendChild($opening_hours_element);
		}

		if ($this->getDesiredDeliveryplace() !== null)
		{
			$tag_name = 'desiredDeliveryPlace';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$box_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getDesiredDeliveryplace()
				)
			);
		}

		if ($this->getReceiver() !== null)
			$box_element->appendChild(
				$this->getReceiver()->toXML($document)
			);

		return $national_element;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return EontechModBpostOrderBoxAtHome
	 * @throws EontechModException
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$at_home = new EontechModBpostOrderBoxAtHome();

		if (isset($xml->atHome->product) && $xml->atHome->product != '')
			$at_home->setProduct((string)$xml->atHome->product);
		if (isset($xml->atHome->options))
			foreach ($xml->atHome->options as $option_data)
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

				$at_home->addOption($option);
			}
		if (isset($xml->atHome->weight) && $xml->atHome->weight != '')
			$at_home->setWeight((int)$xml->atHome->weight);
		if (isset($xml->atHome->openingHours) && $xml->atHome->openingHours != '')
		{
			throw new EontechModException('Not Implemented');
			//$atHome->setProduct(
			//	(string)$xml->atHome->openingHours
			//);
		}
		if (isset($xml->atHome->desiredDeliveryPlace) && $xml->atHome->desiredDeliveryPlace != '')
			$at_home->setDesiredDeliveryplace((string)$xml->atHome->desiredDeliveryPlace);
		if (isset($xml->atHome->receiver))
			$at_home->setReceiver(
				EontechModBpostOrderReceiver::createFromXML(
					$xml->atHome->receiver->children('http://schema.post.be/shm/deepintegration/v3/common')
				)
			);

		return $at_home;
	}
}
