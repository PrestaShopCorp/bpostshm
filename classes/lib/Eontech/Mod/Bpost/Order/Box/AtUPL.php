<?php
/**
 * bPost At247 class
 *
 * @author    Serge Jamasb <serge@stigmi.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Eontech.net. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderBoxAtUPL extends EontechModBpostOrderBoxNational
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
	 * @var EontechModBpostOrderParcelsDepotAddress
	 */
	private $parcels_depot_address;

	/**
	 * @var string
	 */
	protected $product = 'bpack 24h Pro';

	/**
	 * @var EontechModBpostOrderUnregisteredInfo
	 */
	private $unregistered_info;

	/**
	 * @var string
	 */
	/*private $member_id;*/

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
	/*public function setMemberId($member_id)
	{
		$this->member_id = $member_id;
	}*/

	/**
	 * @return string
	 */
	/*public function getMemberId()
	{
		return $this->member_id;
	}*/

	/**
	 * @param EontechModBpostOrderUnregisteredInfo $unregistered_info
	 */
	public function setUnregisteredInfo($unregistered_info)
	{
		$this->unregistered_info = $unregistered_info;
	}

	/**
	 * @return EontechModBpostOrderUnregisteredInfo
	 */
	public function getUnregisteredInfo()
	{
		return $this->unregistered_info;
	}

	/**
	 * @param EontechModBpostOrderParcelsDepotAddress $parcels_depot_address
	 */
	public function setParcelsDepotaddress($parcels_depot_address)
	{
		$this->parcels_depot_address = $parcels_depot_address;
	}

	/**
	 * @return EontechModBpostOrderParcelsDepotAddress
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
			'bpack 24/7',
			'bpack 24h Pro',
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
		if ($this->getUnregisteredInfo() !== null)
			$box_element->appendChild(
				$this->getUnregisteredInfo()->toXML($document)
			);
		/*
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
		*/
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
	 * @return EontechModBpostOrderBoxAtUPL
	 * @throws EontechModException
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$at_upl = new EontechModBpostOrderBoxAtUPL();

		if (isset($xml->{'at24-7'}->product) && $xml->{'at24-7'}->product != '')
			$at_upl->setProduct((string)$xml->{'at24-7'}->product);
		if (isset($xml->{'at24-7'}->options))
			foreach ($xml->{'at24-7'}->options as $option_data)
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

				$at_upl->addOption($option);
			}
		if (isset($xml->{'at24-7'}->weight) && $xml->{'at24-7'}->weight != '')
			$at_upl->setWeight((int)$xml->{'at24-7'}->weight);
		// if (isset($xml->{'at24-7'}->memberId) && $xml->{'at24-7'}->memberId != '')
		// 	$at_upl->setMemberId((string)$xml->{'at24-7'}->memberId);
		if (isset($xml->{'at24-7'}->receiverName) && $xml->{'at24-7'}->receiverName != '')
			$at_upl->setReceiverName((string)$xml->{'at24-7'}->receiverName);
		if (isset($xml->{'at24-7'}->receiverCompany) && $xml->{'at24-7'}->receiverCompany != '')
			$at_upl->setReceiverCompany((string)$xml->{'at24-7'}->receiverCompany);
		if (isset($xml->{'at24-7'}->parcelsDepotId) && $xml->{'at24-7'}->parcelsDepotId != '')
			$at_upl->setParcelsDepotid((string)$xml->{'at24-7'}->parcelsDepotId);
		if (isset($xml->{'at24-7'}->parcelsDepotName) && $xml->{'at24-7'}->parcelsDepotName != '')
			$at_upl->setParcelsDepotname((string)$xml->{'at24-7'}->parcelsDepotName);
		if (isset($xml->{'at24-7'}->parcelsDepotAddress))
		{
			$parcels_depot_address_data = $xml->{'at24-7'}->parcelsDepotAddress->children(
				'http://schema.post.be/shm/deepintegration/v3/common'
			);
			$at_upl->setParcelsDepotaddress(EontechModBpostOrderParcelsDepotAddress::createFromXML($parcels_depot_address_data));
		}
		if (isset($xml->{'at24-7'}->unregistered))
		{
			$unregistered_info_data = $xml->{'at24-7'}->unregistered->children(
				'http://schema.post.be/shm/deepintegration/v3/common'
			);
			$at_upl->setParcelsDepotaddress(EontechModBpostOrderUnregisteredInfo::createFromXML($unregistered_info_data));
		}

		return $at_upl;
	}
}
