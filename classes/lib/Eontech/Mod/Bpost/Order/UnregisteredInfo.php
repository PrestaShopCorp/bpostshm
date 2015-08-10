<?php
/**
 * bPost UnregisteredInfo class
 *
 * @author    Serge Jamasb <serge@stigmi.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Eontech.net. All rights reserved.
 * @license   BSD License
 */

class EontechModBpostOrderUnregisteredInfo
{
	const TAG_NAME = 'unregistered';

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var string
	 */
	private $mobile_phone;

	/**
	 * @var string
	 */
	private $email_address;

	/**
	 * @var int
	 */
	private $reduced_mobility_zone;

	/**
	 * @param string $language
	 * @throws EontechModException
	 */
	public function setLanguage($language)
	{
		$language = \Tools::strtoupper($language);

		if (!in_array($language, self::getPossibleLanguageValues()))
			throw new EontechModException(
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
			// 'DE',
		);
	}

	/**
	 * @param string $mobile_phone
	 * @throws EontechModException
	 */
	public function setMobilePhone($mobile_phone)
	{
		$length = 20;
		if (mb_strlen($mobile_phone) > $length)
			throw new EontechModException(sprintf('Invalid length, maximum is %1$s.', $length));

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
	 * @param string $email_address
	 * @throws EontechModException
	 */
	public function setEmailAddress($email_address)
	{
		$length = 50;
		if (mb_strlen($email_address) > $length)
			throw new EontechModException(sprintf('Invalid length, maximum is %1$s.', $length));

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
	 * @param int $rmz
	 */
	public function setReducedMobilityZone($rmz)
	{
		$this->reduced_mobility_zone = $rmz;
	}

	/**
	 * @return int
	 */
	public function getReducedMobilityZone()
	{
		return $this->reduced_mobility_zone;
	}

	/**
	 * @param string	  $language
	 * @param string	  $email_address
	 * @param string|null $mobile_phone
	 * @param int	  	  $rmz
	 */
	/*
	public function __construct($language, $email_address, $mobile_phone = null, $rmz = false)
	{
		$this->setLanguage($language);

		if ($email_address !== null)
			$this->setEmailAddress($email_address);
		if ($mobile_phone !== null)
			$this->setMobilePhone($mobile_phone);

		$this->setReducedMobilityZone($rmz);
	}
*/
	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DomDocument $document
	 * @param  string	   $prefix
	 * @return \DomElement
	 */
	public function toXML(\DOMDocument $document, $prefix = null)
	{
		$tag_name = static::TAG_NAME;
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$unreg_info = $document->createElement($tag_name);

		if ($this->getLanguage() !== null)
		{
			$tag_name = 'language';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$unreg_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getLanguage()
				)
			);
		}
		if ($this->getMobilePhone() !== null)
		{
			$tag_name = 'mobilePhone';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$unreg_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getMobilePhone()
				)
			);
		}
		if ($this->getEmailAddress() !== null)
		{
			$tag_name = 'emailAddress';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$unreg_info->appendChild(
				$document->createElement(
					$tag_name,
					$this->getEmailAddress()
				)
			);
		}
		if ($this->getReducedMobilityZone() !== null)
		{
			$tag_name = 'reducedMobilityZone';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			if ((bool)$this->getReducedMobilityZone())
				$unreg_info->appendChild(
					$document->createElement($tag_name)
				);
		}

		return $unreg_info;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return EontechModBpostOrderUnregisteredInfo
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$unreg_info = new EontechModBpostOrderUnregisteredInfo();

		if (isset($xml->language) && $xml->language != '')
			$unreg_info->seLanguage((string)$xml->language);
		if (isset($xml->mobilePhone) && $xml->mobilePhone != '')
			$unreg_info->setMobilePhone((string)$xml->mobilePhone);
		if (isset($xml->emailAddress) && $xml->emailAddress != '')
			$unreg_info->setEmailAddress((string)$xml->emailAddress);
		if (isset($xml->reducedMobilityZone))
			$unreg_info->setReducedMobilityZone((bool)$xml->reducedMobilityZone);

		return $unreg_info;
	}
}
