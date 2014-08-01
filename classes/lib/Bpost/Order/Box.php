<?php
/**
 * bPost Box class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostBpostOrderBox
{
	/**
	 * @var TijsVerkoyenBpostBpostOrderSender
	 */
	private $sender;

	/**
	 * @var TijsVerkoyenBpostBpostOrderBoxAtHome
	 */
	private $national_box;

	/**
	 * @var TijsVerkoyenBpostBpostOrderBoxInternational
	 */
	private $international_box;

	/**
	 * @var string
	 */
	private $remark;

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @param TijsVerkoyenBpostBpostOrderBoxInternational $international_box
	 */
	public function setInternationalBox(TijsVerkoyenBpostBpostOrderBoxInternational $international_box)
	{
		$this->international_box = $international_box;
	}

	/**
	 * @return TijsVerkoyenBpostBpostOrderBoxInternational
	 */
	public function getInternationalBox()
	{
		return $this->international_box;
	}

	/**
	 * @param TijsVerkoyenBpostBpostOrderBoxNational $national_box
	 */
	public function setNationalBox(TijsVerkoyenBpostBpostOrderBoxNational $national_box)
	{
		$this->national_box = $national_box;
	}

	/**
	 * @return TijsVerkoyenBpostBpostOrderBoxNational
	 */
	public function getNationalBox()
	{
		return $this->national_box;
	}

	/**
	 * @param string $remark
	 */
	public function setRemark($remark)
	{
		$this->remark = $remark;
	}

	/**
	 * @return string
	 */
	public function getRemark()
	{
		return $this->remark;
	}

	/**
	 * @param TijsVerkoyenBpostBpostOrderSender $sender
	 */
	public function setSender(TijsVerkoyenBpostBpostOrderSender $sender)
	{
		$this->sender = $sender;
	}

	/**
	 * @return TijsVerkoyenBpostBpostOrderSender
	 */
	public function getSender()
	{
		return $this->sender;
	}

	/**
	 * @param string $status
	 * @throws TijsVerkoyenBpostException
	 */
	public function setStatus($status)
	{
		$status = \Tools::strtoupper($status);
		if (!in_array($status, self::getPossibleStatusValues()))
			throw new TijsVerkoyenBpostException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleStatusValues())
				)
			);

		$this->status = $status;
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * @return array
	 */
	public static function getPossibleStatusValues()
	{
		return array(
			'OPEN',
			'PENDING',
			'CANCELLED',
			'COMPLETED',
			'ON-HOLD',
			'PRINTED',
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
		$tag_name = 'box';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$box = $document->createElement($tag_name);

		if ($this->getSender() !== null)
			$box->appendChild(
				$this->getSender()->toXML($document, $prefix)
			);
		if ($this->getNationalBox() !== null)
			$box->appendChild(
				$this->getNationalBox()->toXML($document, $prefix)
			);
		if ($this->getInternationalBox() !== null)
			$box->appendChild(
				$this->getInternationalBox()->toXML($document, $prefix)
			);
		if ($this->getRemark() !== null)
		{
			$tag_name = 'remark';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$box->appendChild(
				$document->createElement(
					$tag_name,
					$this->getRemark()
				)
			);
		}

		return $box;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return TijsVerkoyenBpostBpostOrderBox
	 * @throws TijsVerkoyenBpostException
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$box = new TijsVerkoyenBpostBpostOrderBox();
		if (isset($xml->sender))
			$box->setSender(
				TijsVerkoyenBpostBpostOrderSender::createFromXML(
					$xml->sender->children(
						'http://schema.post.be/shm/deepintegration/v3/common'
					)
				)
			);
		if (isset($xml->nationalBox))
		{
			$national_box_data = $xml->nationalBox->children('http://schema.post.be/shm/deepintegration/v3/national');

			// build classname based on the tag name
			$className = 'TijsVerkoyenBpostBpostOrderBox'.\Tools::ucfirst($national_box_data->getName());
			if ($national_box_data->getName() == 'at24-7')
				$className = 'TijsVerkoyenBpostBpostOrderBoxat247';

			if (!method_exists($className, 'createFromXML'))
				throw new TijsVerkoyenBpostException('Not Implemented');

			$national_box = call_user_func(
				array($className, 'createFromXML'),
				$national_box_data
			);

			$box->setNationalBox($national_box);
		}
		if (isset($xml->internationalBox))
		{
			$international_box_data = $xml->internationalBox->children('http://schema.post.be/shm/deepintegration/v3/international');

			// build classname based on the tag name
			$className = 'TijsVerkoyenBpostBpostOrderBox'.\Tools::ucfirst($international_box_data->getName());

			if (!method_exists($className, 'createFromXML'))
				throw new TijsVerkoyenBpostException('Not Implemented');

			$international_box = call_user_func(
				array($className, 'createFromXML'),
				$international_box_data
			);

			$box->setInternationalBox($international_box);
		}
		if (isset($xml->remark) && $xml->remark != '')
			$box->setRemark((string)$xml->remark);
		if (isset($xml->status) && $xml->status != '')
			$box->setStatus((string)$xml->status);

		return $box;
	}
}
