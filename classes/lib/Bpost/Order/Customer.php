<?php
/**
 * bPost Customer class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostBpostOrderCustomer
{
	const TAG_NAME = 'customer';

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $company;

	/**
	 * @var TijsVerkoyenBpostBpostOrderAddress
	 */
	private $address;

	/**
	 * @var string
	 */
	private $email_address;

	/**
	 * @var string
	 */
	private $phone_number;

	/**
	 * @param TijsVerkoyenBpostBpostOrderAddress $address
	 */
	public function setAddress($address)
	{
		$this->address = $address;
	}

	/**
	 * @return TijsVerkoyenBpostBpostOrderAddress
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * @param string $company
	 */
	public function setCompany($company)
	{
		$this->company = $company;
	}

	/**
	 * @return string
	 */
	public function getCompany()
	{
		return $this->company;
	}

	/**
	 * @param string $email_address
	 * @throws TijsVerkoyenBpostException
	 */
	public function setEmailAddress($email_address)
	{
		$length = 50;
		if (mb_strlen($email_address) > $length)
			throw new TijsVerkoyenBpostException(sprintf('Invalid length, maximum is %1$s.', $length));
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
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $phone_number
	 * @throws TijsVerkoyenBpostException
	 */
	public function setPhoneNumber($phone_number)
	{
		$length = 20;
		if (mb_strlen($phone_number) > $length)
			throw new TijsVerkoyenBpostException(sprintf('Invalid length, maximum is %1$s.', $length));
		$this->phone_number = $phone_number;
	}

	/**
	 * @return string
	 */
	public function getPhoneNumber()
	{
		return $this->phone_number;
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param \DomDocument
	 * @param  string	  $prefix
	 * @return \DomElement
	 */
	public function toXML(\DomDocument $document, $prefix = null)
	{
		$tag_name = static::TAG_NAME;
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$customer = $document->createElement($tag_name);

		if ($this->getName() !== null)
			$customer->appendChild(
				$document->createElement(
					'common:name',
					$this->getName()
				)
			);
		if ($this->getCompany() !== null)
			$customer->appendChild(
				$document->createElement(
					'common:company',
					$this->getCompany()
				)
			);
		if ($this->getAddress() !== null)
			$customer->appendChild(
				$this->getAddress()->toXML($document)
			);
		if ($this->getEmailAddress() !== null)
			$customer->appendChild(
				$document->createElement(
					'common:emailAddress',
					$this->getEmailAddress()
				)
			);
		if ($this->getPhoneNumber() !== null)
			$customer->appendChild(
				$document->createElement(
					'common:phoneNumber',
					$this->getPhoneNumber()
				)
			);

		return $customer;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @param  TijsVerkoyenBpostBpostOrderCustomer		  $instance
	 * @return TijsVerkoyenBpostBpostOrderCustomer
	 */
	public static function createFromXMLHelper(\SimpleXMLElement $xml, TijsVerkoyenBpostBpostOrderCustomer $instance)
	{
		if (isset($xml->name) && $xml->name != '')
			$instance->setName((string)$xml->name);
		if (isset($xml->company) && $xml->company != '')
			$instance->setCompany((string)$xml->company);
		if (isset($xml->address))
			$instance->setAddress(TijsVerkoyenBpostBpostOrderAddress::createFromXML($xml->address));
		if (isset($xml->emailAddress) && $xml->emailAddress != '')
			$instance->setEmailAddress((string)$xml->emailAddress);
		if (isset($xml->phoneNumber) && $xml->phoneNumber != '')
			$instance->setPhoneNumber((string)$xml->phoneNumber);

		return $instance;
	}
}
