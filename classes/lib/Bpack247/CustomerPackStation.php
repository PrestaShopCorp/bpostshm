<?php
/**
 * bPost Customer Pack Station class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

namespace TijsVerkoyen\Bpost\Bpack247;

class CustomerPackStation
{
	/**
	 * @var string
	 */
	private $custom_label;

	/**
	 * @var string
	 */
	private $order_number;

	/**
	 * @var string
	 */
	private $packstation_id;

	/**
	 * @param string $custom_label
	 */
	public function setCustomLabel($custom_label)
	{
		$this->custom_label = $custom_label;
	}

	/**
	 * @return string
	 */
	public function getCustomLabel()
	{
		return $this->custom_label;
	}

	/**
	 * @param string $order_number
	 */
	public function setOrderNumber($order_number)
	{
		$this->order_number = $order_number;
	}

	/**
	 * @return string
	 */
	public function getOrderNumber()
	{
		return $this->order_number;
	}

	/**
	 * @param string $packstation_id
	 */
	public function setPackstationId($packstation_id)
	{
		$this->packstation_id = $packstation_id;
	}

	/**
	 * @return string
	 */
	public function getPackstationId()
	{
		return $this->packstation_id;
	}

	/**
	 * @param  \SimpleXMLElement   $xml
	 * @return CustomerPackStation
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		$pack_station = new CustomerPackStation();

		if (isset($xml->orderNumber) && $xml->orderNumber != '')
			$pack_station->setOrderNumber((string)$xml->orderNumber);
		if (isset($xml->customLabel) && $xml->customLabel != '')
			$pack_station->setCustomLabel((string)$xml->customLabel);
		if (isset($xml->packstationId) && $xml->packstationId != '')
			$pack_station->setPackstationId((string)$xml->packstationId);

		return $pack_station;
	}
}
