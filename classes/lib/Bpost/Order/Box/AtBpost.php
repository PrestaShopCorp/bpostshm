<?php
/**
 * bPost AtBpost class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

namespace TijsVerkoyen\Bpost\Bpost\Order\Box;

use TijsVerkoyen\Bpost\Bpost\Order\PugoAddress;
use TijsVerkoyen\Bpost\Exception;
use TijsVerkoyen\Bpost\Bpost\Order\Box\Option\Messaging;

class AtBpost extends National
{
	/**
	 * @var string
	 */
	protected $product = 'bpack@bpost';

	/**
	 * @var string
	 */
	private $pugo_id;

	/**
	 * @var string
	 */
	private $pugo_name;

	/**
	 * @var \TijsVerkoyen\Bpost\Bpost\Order\PugoAddress;
	 */
	private $pugo_address;

	/**
	 * @var string
	 */
	private $receiver_name;

	/**
	 * @var string
	 */
	private $receiver_company;

	/**
	 * @param string $product Possible values are: bpack@bpost
	 * @throws Exception
	 */
	public function setProduct($product)
	{
		if (!in_array($product, self::getPossibleProductValues()))
			throw new Exception(
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
			'bpack@bpost',
		);
	}

	/**
	 * @param \TijsVerkoyen\Bpost\Bpost\Order\PugoAddress $pugo_address
	 */
	public function setPugoAddress($pugo_address)
	{
		$this->pugo_address = $pugo_address;
	}

	/**
	 * @return \TijsVerkoyen\Bpost\Bpost\Order\PugoAddress
	 */
	public function getPugoAddress()
	{
		return $this->pugo_address;
	}

	/**
	 * @param string $pugo_id
	 */
	public function setPugoId($pugo_id)
	{
		$this->pugo_id = $pugo_id;
	}

	/**
	 * @return string
	 */
	public function getPugoId()
	{
		return $this->pugo_id;
	}

	/**
	 * @param string $pugo_name
	 */
	public function setPugoName($pugo_name)
	{
		$this->pugo_name = $pugo_name;
	}

	/**
	 * @return string
	 */
	public function getPugoName()
	{
		return $this->pugo_name;
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
		$box_element = parent::toXML($document, null, 'atBpost');
		$national_element->appendChild($box_element);

		if ($this->getPugoId() !== null)
		{
			$tag_name = 'pugoId';
			$box_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getPugoId()
				)
			);
		}
		if ($this->getPugoName() !== null)
		{
			$tag_name = 'pugoName';
			$box_element->appendChild(
				$document->createElement(
					$tag_name,
					$this->getPugoName()
				)
			);
		}
		if ($this->getPugoAddress() !== null)
			$box_element->appendChild(
				$this->getPugoAddress()->toXML($document, 'common')
			);
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
	 * @return AtBpost
	 * @throws Exception
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$at_bpost = new AtBpost();

		if (isset($xml->atBpost->product) && $xml->atBpost->product != '')
			$at_bpost->setProduct((string)$xml->atBpost->product);
		if (isset($xml->atBpost->options))
			foreach ($xml->atBpost->options as $option_data)
			{
				$option_data = $option_data->children('http://schema.post.be/shm/deepintegration/v3/common');

				if (in_array(
					$option_data->getName(),
					array(
						'infoDistributed',
						'infoNextDay',
						'infoReminder',
						'keepMeInformed',
					)))
					$option = Messaging::createFromXML($option_data);
				else
				{
					$class_name = '\\TijsVerkoyen\\Bpost\\Bpost\\Order\\Box\\Option\\'.\Tools::ucfirst($option_data->getName());
					if (!method_exists($class_name, 'createFromXML'))
						throw new Exception('Not Implemented');
					$option = call_user_func(
						array($class_name, 'createFromXML'),
						$option_data
					);
				}

				$at_bpost->addOption($option);
			}
		if (isset($xml->atBpost->weight) && $xml->atBpost->weight != '')
			$at_bpost->setWeight((int)$xml->atBpost->weight);
		if (isset($xml->atBpost->receiverName) && $xml->atBpost->receiverName != '')
			$at_bpost->setReceiverName((string)$xml->atBpost->receiverName);
		if (isset($xml->atBpost->receiverCompany) && $xml->atBpost->receiverCompany != '')
			$at_bpost->setReceiverCompany((string)$xml->atBpost->receiverCompany);
		if (isset($xml->atBpost->pugoId) && $xml->atBpost->pugoId != '')
			$at_bpost->setPugoId((string)$xml->atBpost->pugoId);
		if (isset($xml->atBpost->pugoName) && $xml->atBpost->pugoName != '')
			$at_bpost->setPugoName((string)$xml->atBpost->pugoName);
		if (isset($xml->atBpost->pugoAddress))
		{
			$pugo_address_data = $xml->atBpost->pugoAddress->children(
				'http://schema.post.be/shm/deepintegration/v3/common'
			);
			$at_bpost->setPugoAddress(
				PugoAddress::createFromXML($pugo_address_data)
			);
		}

		return $at_bpost;
	}
}
