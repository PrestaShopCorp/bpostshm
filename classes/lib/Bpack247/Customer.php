<?php
/**
 * bPost Customer class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostBpack247Customer
{
	/**
	 * @var bool
	 */
	private $activated;

	/**
	 * @var string
	 */
	private $user_id;

	/**
	 * @var string
	 */
	private $first_name;

	/**
	 * @var string
	 */
	private $last_name;

	/**
	 * @var string
	 */
	private $company_name;

	/**
	 * @var string
	 */
	private $street;

	/**
	 * @var string
	 */
	private $number;

	/**
	 * @var string
	 */
	private $email;

	/**
	 * @var string
	 */
	private $mobile_prefix = '0032';

	/**
	 * @var string
	 */
	private $mobile_number;

	/**
	 * @var string
	 */
	private $postal_code;

	/**
	 * @var array
	 */
	private $pack_stations = array();

	/**
	 * @var string
	 */
	private $town;

	/**
	 * @var string
	 */
	private $preferred_language;

	/**
	 * @var string
	 */
	private $title;

	/**
	 * @var bool
	 */
	private $is_comfort_zone_user;

	/**
	 * @var \DateTime
	 */
	private $date_of_birth;

	/**
	 * @var string
	 */
	private $delivery_code;

	/**
	 * @var bool
	 */
	private $opt_in;

	/**
	 * @var bool
	 */
	private $receive_promotions;

	/**
	 * @var bool
	 */
	private $use_information_for_third_party;

	/**
	 * @var string
	 */
	private $user_name;

	/**
	 * @param boolean $activated
	 */
	public function setActivated($activated)
	{
		$this->activated = $activated;
	}

	/**
	 * @return boolean
	 */
	public function getActivated()
	{
		return $this->activated;
	}

	/**
	 * @param string $company_name
	 */
	public function setCompanyName($company_name)
	{
		$this->company_name = $company_name;
	}

	/**
	 * @return string
	 */
	public function getCompanyName()
	{
		return $this->company_name;
	}

	/**
	 * @param \DateTime $date_of_birth
	 */
	public function setDateOfbirth($date_of_birth)
	{
		$this->date_of_birth = $date_of_birth;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateOfbirth()
	{
		return $this->date_of_birth;
	}

	/**
	 * @param string $delivery_code
	 */
	public function setDeliveryCode($delivery_code)
	{
		$this->delivery_code = $delivery_code;
	}

	/**
	 * @return string
	 */
	public function getDeliveryCode()
	{
		return $this->delivery_code;
	}

	/**
	 * @param string $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @param string $first_name
	 */
	public function setFirstName($first_name)
	{
		$this->first_name = $first_name;
	}

	/**
	 * @return string
	 */
	public function getFirstName()
	{
		return $this->first_name;
	}

	/**
	 * @param boolean $is_comfort_zone_user
	 */
	public function setIsComfortzoneuser($is_comfort_zone_user)
	{
		$this->is_comfort_zone_user = $is_comfort_zone_user;
	}

	/**
	 * @return boolean
	 */
	public function getIsComfortzoneuser()
	{
		return $this->is_comfort_zone_user;
	}

	/**
	 * @param string $last_name
	 */
	public function setLastName($last_name)
	{
		$this->last_name = $last_name;
	}

	/**
	 * @return string
	 */
	public function getLastName()
	{
		return $this->last_name;
	}

	/**
	 * @param string $mobile_number
	 */
	public function setMobileNumber($mobile_number)
	{
		$this->mobile_number = $mobile_number;
	}

	/**
	 * @return string
	 */
	public function getMobileNumber()
	{
		return $this->mobile_number;
	}

	/**
	 * @param string $mobile_prefix
	 */
	public function setMobilePrefix($mobile_prefix)
	{
		$this->mobile_prefix = $mobile_prefix;
	}

	/**
	 * @return string
	 */
	public function getMobilePrefix()
	{
		return $this->mobile_prefix;
	}

	/**
	 * @param string $number
	 */
	public function setNumber($number)
	{
		$this->number = $number;
	}

	/**
	 * @return string
	 */
	public function getNumber()
	{
		return $this->number;
	}

	/**
	 * @param boolean $opt_in
	 */
	public function setOptIn($opt_in)
	{
		$this->opt_in = $opt_in;
	}

	/**
	 * @return boolean
	 */
	public function getOptIn()
	{
		return $this->opt_in;
	}

	/**
	 * @param TijsVerkoyenBpostBpack247CustomerPackStation $pack_station
	 */
	public function addPackStation(TijsVerkoyenBpostBpack247CustomerPackStation $pack_station)
	{
		$this->pack_stations[] = $pack_station;
	}

	/**
	 * @param array $pack_stations
	 */
	public function setPackStations($pack_stations)
	{
		$this->pack_stations = $pack_stations;
	}

	/**
	 * @return array
	 */
	public function getPackStations()
	{
		return $this->pack_stations;
	}

	/**
	 * @param string $postal_code
	 */
	public function setPostalCode($postal_code)
	{
		$this->postal_code = $postal_code;
	}

	/**
	 * @return string
	 */
	public function getPostalCode()
	{
		return $this->postal_code;
	}

	/**
	 * @param string $preferred_language
	 * @throws TijsVerkoyenBpostException
	 */
	public function setPreferredLanguage($preferred_language)
	{
		if (!in_array($preferred_language, self::getPossiblePreferredLanguageValues()))
			throw new TijsVerkoyenBpostException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossiblePreferredLanguageValues())
				)
			);

		$this->preferred_language = $preferred_language;
	}

	/**
	 * @return string
	 */
	public function getPreferredLanguage()
	{
		return $this->preferred_language;
	}

	/**
	 * @return array
	 */
	public static function getPossiblePreferredLanguageValues()
	{
		return array(
			'nl-BE',
			'fr-BE',
			'en-US',
		);
	}

	/**
	 * @param boolean $receive_promotions
	 */
	public function setReceivePromotions($receive_promotions)
	{
		$this->receive_promotions = $receive_promotions;
	}

	/**
	 * @return boolean
	 */
	public function getReceivePromotions()
	{
		return $this->receive_promotions;
	}

	/**
	 * @param string $street
	 */
	public function setStreet($street)
	{
		$this->street = $street;
	}

	/**
	 * @return string
	 */
	public function getStreet()
	{
		return $this->street;
	}

	/**
	 * @param string $title
	 * @throws TijsVerkoyenBpostException
	 */
	public function setTitle($title)
	{
		if (!in_array($title, self::getPossibleTitleValues()))
			throw new TijsVerkoyenBpostException(
				sprintf(
					'Invalid value, possible values are: %1$s.',
					implode(', ', self::getPossibleTitleValues())
				)
			);

		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @return array
	 */
	public static function getPossibleTitleValues()
	{
		return array(
			'Mr.',
			'Ms.',
		);
	}

	/**
	 * @param string $town
	 */
	public function setTown($town)
	{
		$this->town = $town;
	}

	/**
	 * @return string
	 */
	public function getTown()
	{
		return $this->town;
	}

	/**
	 * @param boolean $use_information_for_third_party
	 */
	public function setUseInformationforthirdparty($use_information_for_third_party)
	{
		$this->use_information_for_third_party = $use_information_for_third_party;
	}

	/**
	 * @return boolean
	 */
	public function getUseInformationforthirdparty()
	{
		return $this->use_information_for_third_party;
	}

	/**
	 * @param string $user_id
	 */
	public function setUserId($user_id)
	{
		$this->user_id = $user_id;
	}

	/**
	 * @return string
	 */
	public function getUserId()
	{
		return $this->user_id;
	}

	/**
	 * @param string $user_name
	 */
	public function setUserName($user_name)
	{
		$this->user_name = $user_name;
	}

	/**
	 * @return string
	 */
	public function getUserName()
	{
		return $this->user_name;
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DOMDocument $document
	 * @return \DOMElement
	 */
	public function toXML(\DOMDocument $document)
	{
		$customer = $document->createElement(
			'Customer'
		);
		$customer->setAttribute(
			'xmlns',
			'http://schema.post.be/ServiceController/customer'
		);
		$customer->setAttribute(
			'xmlns:xsi',
			'http://www.w3.org/2001/XMLSchema-instance'
		);
		$customer->setAttribute(
			'xsi:schemaLocation',
			'http://schema.post.be/ServiceController/customer'
		);

		$document->appendChild($customer);

		if ($this->getFirstName() !== null)
			$customer->appendChild(
				$document->createElement(
					'FirstName',
					$this->getFirstName()
				)
			);
		if ($this->getLastName() !== null)
			$customer->appendChild(
				$document->createElement(
					'LastName',
					$this->getLastName()
				)
			);
		if ($this->getStreet() !== null)
			$customer->appendChild(
				$document->createElement(
					'Street',
					$this->getStreet()
				)
			);
		if ($this->getNumber() !== null)
			$customer->appendChild(
				$document->createElement(
					'Number',
					$this->getNumber()
				)
			);
		if ($this->getEmail() !== null)
			$customer->appendChild(
				$document->createElement(
					'Email',
					$this->getEmail()
				)
			);
		if ($this->getMobilePrefix() !== null)
			$customer->appendChild(
				$document->createElement(
					'MobilePrefix',
					$this->getMobilePrefix()
				)
			);
		if ($this->getMobileNumber() !== null)
			$customer->appendChild(
				$document->createElement(
					'MobileNumber',
					$this->getMobileNumber()
				)
			);
		if ($this->getPostalCode() !== null)
			$customer->appendChild(
				$document->createElement(
					'Postalcode',
					$this->getPostalCode()
				)
			);
		if ($this->getPreferredLanguage() !== null)
			$customer->appendChild(
				$document->createElement(
					'PreferredLanguage',
					$this->getPreferredLanguage()
				)
			);
		if ($this->getTitle() !== null)
			$customer->appendChild(
				$document->createElement(
					'Title',
					$this->getTitle()
				)
			);

		return $customer;
	}

	/**
	 * @param \SimpleXMLElement $xml
	 * @return TijsVerkoyenBpostBpack247Customer
	 * @throws TijsVerkoyenBpostException
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		// @todo work with classmaps ...
		if (!isset($xml->UserID))
			throw new TijsVerkoyenBpostException('No UserId found.');

		$customer = new TijsVerkoyenBpostBpack247Customer();

		if (isset($xml->UserID) && $xml->UserID != '')
			$customer->setUserID((string)$xml->UserID);
		if (isset($xml->FirstName) && $xml->FirstName != '')
			$customer->setFirstName((string)$xml->FirstName);
		if (isset($xml->LastName) && $xml->LastName != '')
			$customer->setLastName((string)$xml->LastName);
		if (isset($xml->Street) && $xml->Street != '')
			$customer->setStreet((string)$xml->Street);
		if (isset($xml->Number) && $xml->Number != '')
			$customer->setNumber((string)$xml->Number);
		if (isset($xml->CompanyName) && $xml->CompanyName != '')
			$customer->setCompanyName((string)$xml->CompanyName);
		if (isset($xml->DateOfBirth) && $xml->DateOfBirth != '')
		{
			$date_time = new \DateTime((string)$xml->DateOfBirth);
			$customer->setDateOfBirth($date_time);
		}
		if (isset($xml->DeliveryCode) && $xml->DeliveryCode != '')
			$customer->setDeliveryCode((string)$xml->DeliveryCode);
		if (isset($xml->Email) && $xml->Email != '')
			$customer->setEmail((string)$xml->Email);
		if (isset($xml->MobilePrefix) && $xml->MobilePrefix != '')
			$customer->setMobilePrefix(trim((string)$xml->MobilePrefix) );
		if (isset($xml->MobileNumber) && $xml->MobileNumber != '')
			$customer->setMobileNumber((string)$xml->MobileNumber);
		if (isset($xml->Postalcode) && $xml->Postalcode != '')
			$customer->setPostalCode((string)$xml->Postalcode);
		if (isset($xml->PreferredLanguage) && $xml->PreferredLanguage != '')
			$customer->setPreferredLanguage((string)$xml->PreferredLanguage);
		if (isset($xml->ReceivePromotions) && $xml->ReceivePromotions != '')
		{
			$receive_promotions = in_array((string)$xml->ReceivePromotions, array('true', '1'));
			$customer->setReceivePromotions($receive_promotions);
		}
		if (isset($xml->actived) && $xml->actived != '')
		{
			$activated = in_array((string)$xml->actived, array('true', '1'));
			$customer->setActivated($activated);
		}
		if (isset($xml->Title) && $xml->Title != '')
		{
			$title = (string)$xml->Title;
			$title = \Tools::ucfirst(\Tools::strtolower($title));
			if (\Tools::substr($title, -1) != '.')
				$title .= '.';

			$customer->setTitle($title);
		}
		if (isset($xml->Town) && $xml->Town != '')
			$customer->setTown((string)$xml->Town);

		if (isset($xml->PackStations->CustomerPackStation))
			foreach ($xml->PackStations->CustomerPackStation as $pack_station)
				$customer->addPackStation(TijsVerkoyenBpostBpack247CustomerPackStation::createFromXML($pack_station));

		return $customer;
	}
}
