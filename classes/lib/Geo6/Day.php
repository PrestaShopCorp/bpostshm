<?php
/**
 * Geo6 class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

namespace TijsVerkoyen\Bpost\Geo6;

use TijsVerkoyen\Bpost\Exception;

class Day
{
	/**
	 * @var string
	 */
	private $am_open;

	/**
	 * @var string
	 */
	private $am_close;

	/**
	 * @var string
	 */
	private $pm_open;

	/**
	 * @var string
	 */
	private $pm_close;

	/**
	 * @var string
	 */
	private $day;

	/**
	 * @param string $am_close
	 */
	public function setAmClose($am_close)
	{
		$this->am_close = $am_close;
	}

	/**
	 * @return string
	 */
	public function getAmClose()
	{
		return $this->am_close;
	}

	/**
	 * @param string $am_open
	 */
	public function setAmOpen($am_open)
	{
		$this->am_open = $am_open;
	}

	/**
	 * @return string
	 */
	public function getAmOpen()
	{
		return $this->am_open;
	}

	/**
	 * @param string $day
	 */
	public function setDay($day)
	{
		$this->day = $day;
	}

	/**
	 * @return string
	 */
	public function getDay()
	{
		return $this->day;
	}

	/**
	 * Get the index for a day
	 *
	 * @return int
	 */
	public function getDayIndex()
	{
		switch (\Tools::strtolower($this->getDay()))
		{
			case 'monday':
				return 1;
			case 'tuesday':
				return 2;
			case 'wednesday':
				return 3;
			case 'thursday':
				return 4;
			case 'friday':
				return 5;
			case 'saturday':
				return 6;
			case 'sunday':
				return 7;
		}

		throw new Exception('Invalid day.');
	}

	/**
	 * @param string $pm_close
	 */
	public function setPmClose($pm_close)
	{
		$this->pm_close = $pm_close;
	}

	/**
	 * @return string
	 */
	public function getPmClose()
	{
		return $this->pm_close;
	}

	/**
	 * @param string $pm_open
	 */
	public function setPmOpen($pm_open)
	{
		$this->pm_open = $pm_open;
	}

	/**
	 * @return string
	 */
	public function getPmOpen()
	{
		return $this->pm_open;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return Day
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$day = new Day();
		$day->setDay($xml->getName());

		if (isset($xml->AMOpen) && $xml->AMOpen != '')
			$day->setAmOpen((string)$xml->AMOpen);
		if (isset($xml->AMClose) && $xml->AMClose != '')
			$day->setAmClose((string)$xml->AMClose);
		if (isset($xml->PMOpen) && $xml->PMOpen != '')
			$day->setPmOpen((string)$xml->PMOpen);
		if (isset($xml->PMClose) && $xml->PMClose != '')
			$day->setPmClose((string)$xml->PMClose);

		return $day;
	}
}
