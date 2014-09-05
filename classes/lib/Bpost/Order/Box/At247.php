<?php
/**
 * bPost At247 class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostBpostOrderBoxAt247 extends TijsVerkoyenBpostBpostOrderBoxNational
{
	/**
	 * @var string
	 */
	private $parcels_depot_id;

	/**
	 * @var string
	 */
	private $parcels_depot_name;

	/**
	 * @var TijsVerkoyenBpostBpostOrderParcelsDepotAddress
	 */
	private $parcels_depot_address;

	/**
	 * @var string
	 */
	protected $product = 'bpack 24/7';

	/**
	 * @var string
	 */
	private $member_id;

	/**
	 * @var string
	 */
	private $receiver_name;

	/**
	 * @var string
	 */
	private $receiver_company;

	/**
	 * @param string $member_id
	 */
	public function setMemberId($member_id)
	{
		$this->member_id = $member_id;
	}

	/**
	 * @return string
	 */
	public function getMemberId()
	{
		return $this->member_id;
	}

	/**
	 * @param TijsVerkoyenBpostBpostOrderParcelsDepotAddress $parcels_depot_address
	 */
	public function setParcelsDepotaddress($parcels_depot_address)
	{
		$this->parcels_depot_address = $parcels_depot_address;
	}

	/**
	 * @return TijsVerkoyenBpostBpostOrderParcelsDepotAddress
	 */
	public function getParcelsDepotaddress()
	{
		return $this->parcels_depot_address;
	}

	/**
	 * @param string $parcels_depot_id
	 */
	public function setParcelsDepotid($parcels_depot_id)
	{
		$this->parcels_depot_id = $parcels_depot_id;
	}

	/**
	 * @return string
	 */
	public function getParcelsDepotid()
	{
		return $this->parcels_depot_id;
	}

	/**
	 * @param string $parcels_depot_name
	 */
	public function setParcelsDepotname($parcels_depot_name)
	{
		$this->parcels_depot_name = $parcels_depot_name;
	}

	/**
	 * @return string
	 */
	public function getParcelsDepotname()
	{
		return $this->parcels_depot_name;
	}

	/**
	 * @param string $product Possible values are: bpack 24/7
	 * @throws TijsVerkoyenBpostException
	 */
	public function setProduct($product)
	{
		if (!in_array($product, self::getPossibleProductValues()))
			throw new TijsVerkoyenBpostException(
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
			'bpack 24/7',
		);
	}

	/**
	 * @param string $receiver_company
	 */
	public function setReceiverCompany($receiver_company)
	{
		$this->receiver_company = $receiver_company;
	}

	/**
	 * @return string
	 */
	public function getReceiverCompany()
	{
		return $this->receiver_company;
	}

	/**
	 * @param string $receiver_name
	 */
	public function setReceiverName($receiver_name)
	{
		$this->receiver_name = $receiver_name;
	}

	/**
	 * @return string
	 */
	public function getReceiverName()
	{
		return $this->receiver_name;
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
		$tag_name = 'nationalBox';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;
		$national_element = $document->createElement($tag_name);
		$box_element = parent::toXML($document, null, 'at24-7');
		$national_element->appendChild($box_element);

		if ($this->getParcelsDepotid() !== null)
		{
			$tag_name = 'parcelsDepotId';
			$box_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getParcelsDepotid()
				)
			);
		}
		if ($this->getParcelsDepotname() !== null)
		{
			$tag_name = 'parcelsDepotName';
			$box_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getParcelsDepotname()
				)
			);
		}
		if ($this->getParcelsDepotaddress() !== null)
			$box_element->appendChild(
				$this->getParcelsDepotaddress()->toXML($document)
			);
		if ($this->getMemberId() !== null)
		{
			$tag_name = 'memberId';
			$box_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getMemberId()
				)
			);
		}
		if ($this->getReceiverName() !== null)
		{
			$tag_name = 'receiverName';
			$box_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getReceiverName()
				)
			);
		}
		if ($this->getReceiverCompany() !== null)
		{
			$tag_name = 'receiverCompany';
			$box_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getReceiverCompany()
				)
			);
		}

		return $national_element;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return TijsVerkoyenBpostBpostOrderBoxAt247
	 * @throws TijsVerkoyenBpostException
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$at247 = new TijsVerkoyenBpostBpostOrderBoxAt247();

		if (isset($xml->{'at24-7'}->product) && $xml->{'at24-7'}->product != '')
			$at247->setProduct((string)$xml->{'at24-7'}->product);
		if (isset($xml->{'at24-7'}->options))
			foreach ($xml->{'at24-7'}->options as $option_data)
			{
				$option_data = $option_data->children('http://schema.post.be/shm/deepintegration/v3/common');

				if (in_array($option_data->getName(), array('infoDistributed')))
					$option = TijsVerkoyenBpostBpostOrderBoxOptionMessaging::createFromXML($option_data);
				else
				{
					$class_name = 'TijsVerkoyenBpostBpostOrderBoxOption'.\Tools::ucfirst($option_data->getName());
					if (!method_exists($class_name, 'createFromXML'))
						throw new TijsVerkoyenBpostException('Not Implemented');
					$option = call_user_func(
						array($class_name, 'createFromXML'),
						$option_data
					);
				}

				$at247->addOption($option);
			}
		if (isset($xml->{'at24-7'}->weight) && $xml->{'at24-7'}->weight != '')
			$at247->setWeight((int)$xml->{'at24-7'}->weight);
		if (isset($xml->{'at24-7'}->memberId) && $xml->{'at24-7'}->memberId != '')
			$at247->setMemberId((string)$xml->{'at24-7'}->memberId);
		if (isset($xml->{'at24-7'}->receiverName) && $xml->{'at24-7'}->receiverName != '')
			$at247->setReceiverName((string)$xml->{'at24-7'}->receiverName);
		if (isset($xml->{'at24-7'}->receiverCompany) && $xml->{'at24-7'}->receiverCompany != '')
			$at247->setReceiverCompany((string)$xml->{'at24-7'}->receiverCompany);
		if (isset($xml->{'at24-7'}->parcelsDepotId) && $xml->{'at24-7'}->parcelsDepotId != '')
			$at247->setParcelsDepotid((string)$xml->{'at24-7'}->parcelsDepotId);
		if (isset($xml->{'at24-7'}->parcelsDepotName) && $xml->{'at24-7'}->parcelsDepotName != '')
			$at247->setParcelsDepotname((string)$xml->{'at24-7'}->parcelsDepotName);
		if (isset($xml->{'at24-7'}->parcelsDepotAddress))
		{
			$parcels_depot_address_data = $xml->{'at24-7'}->parcelsDepotAddress->children(
				'http://schema.post.be/shm/deepintegration/v3/common'
			);
			$at247->setParcelsDepotaddress(TijsVerkoyenBpostBpostOrderParcelsDepotAddress::createFromXML($parcels_depot_address_data));
		}

		return $at247;
	}
}
