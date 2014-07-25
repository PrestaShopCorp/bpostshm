<?php
/**
 * bPost Messaging class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

namespace TijsVerkoyen\Bpost\Bpost\Order\Box\Option;

use TijsVerkoyen\Bpost\Exception;

class Messaging extends Option
{
	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var string
	 */
	private $email_address;

	/**
	 * @var string
	 */
	private $mobile_phone;

	/**
	 * @param string $email_address
	 * @throws Exception
	 */
	public function setEmailAddress($email_address)
	{
		$length = 50;
		if (mb_strlen($email_address) > $length)
			throw new Exception(sprintf('Invalid length, maximum is %1$s.', $length));

		$this->email_address = $email_address;
	}

	/**
	 * @return string
	 */
	public function getEmailAddress()
	{
		return $this->email_address;
	}

	/**
	 * @param string $language
	 * @throws Exception
	 */
	public function setLanguage($language)
	{
		$language = \Tools::strtoupper($language);

		if (!in_array($language, self::getPossibleLanguageValues()))
			throw new Exception(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleLanguageValues())
				)
			);

		$this->language = $language;
	}

	/**
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * @return array
	 */
	public static function getPossibleLanguageValues()
	{
		return array(
			'EN',
			'NL',
			'FR',
			'DE',
		);
	}

	/**
	 * @param string $mobile_phone
	 * @throws Exception
	 */
	public function setMobilePhone($mobile_phone)
	{
		$length = 20;
		if (mb_strlen($mobile_phone) > $length)
			throw new Exception(sprintf('Invalid length, maximum is %1$s.', $length));

		$this->mobile_phone = $mobile_phone;
	}

	/**
	 * @return string
	 */
	public function getMobilePhone()
	{
		return $this->mobile_phone;
	}

	/**
	 * @return array
	 */
	public static function getPossibleTypeValues()
	{
		return array(
			'infoDistributed',
			'infoNextDay',
			'infoReminder',
			'keepMeInformed',
		);
	}

	/**
	 * @param string $type
	 * @throws Exception
	 */
	public function setType($type)
	{
		if (!in_array($type, self::getPossibleTypeValues()))
			throw new Exception(
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
	 * @param string	  $type
	 * @param string	  $language
	 * @param string|null $email_address
	 * @param string|null $mobile_phone
	 */
	public function __construct($type, $language, $email_address = null, $mobile_phone = null)
	{
		$this->setType($type);
		$this->setLanguage($language);

		if ($email_address !== null)
			$this->setEmailAddress($email_address);
		if ($mobile_phone !== null)
			$this->setMobilePhone($mobile_phone);
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DomDocument $document
	 * @param  string	   $prefix
	 * @return \DomElement
	 */
	public function toXML(\DOMDocument $document, $prefix = 'common')
	{
		$tag_name = $this->getType();
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$messaging = $document->createElement($tag_name);
		$messaging->setAttribute('language', $this->getLanguage());

		if ($this->getEmailAddress() !== null)
		{
			$tag_name = 'emailAddress';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$messaging->appendChild(
				$document->createElement(
					$tag_name,
					$this->getEmailAddress()
				)
			);
		}
		if ($this->getMobilePhone() !== null)
		{
			$tag_name = 'mobilePhone';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$messaging->appendChild(
				$document->createElement(
					$tag_name,
					$this->getMobilePhone()
				)
			);
		}

		return $messaging;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return Messaging
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$messaging = new Messaging(
			$xml->getName(), (string)$xml->attributes()->language
		);

		$data = $xml->{$xml->getName()};
		if (isset($data->emailAddress) && $data->emailAddress != '')
			$messaging->setEmailAddress((string)$data->emailAddress);
		if (isset($data->mobilePhone) && $data->mobilePhone != '')
			$messaging->setMobilePhone((string)$data->mobilePhone);

		return $messaging;
	}
}
