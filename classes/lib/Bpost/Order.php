<?php
/**
 * bPost Order class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostOrder
{
	/**
	 * Order reference: unique ID used in your web shop to assign to an order.
	 * The value of this parameter is not managed by bpost. If the value
	 * already exists, it will update current order info. Existing boxes will
	 * not be changed, new boxes will be added.
	 *
	 * @var string
	 */
	private $reference;

	/**
	 * This information is used on your invoice and allows you to attribute
	 * different cost centers
	 *
	 * @var string
	 */
	private $cost_center;

	/**
	 * The items that are included in the order.
	 * Order lines are shown in the back end of the Shipping Manager and
	 * facilitate the use of the tool.
	 *
	 * @var array
	 */
	private $lines;

	/**
	 * Box tags
	 *
	 * @var array
	 */
	private $boxes;

	/**
	 * Create an order
	 *
	 * @param string $reference
	 */
	public function __construct($reference)
	{
		$this->setReference($reference);
	}

	/**
	 * @param array $boxes
	 */
	public function setBoxes($boxes)
	{
		$this->boxes = $boxes;
	}

	/**
	 * @return array
	 */
	public function getBoxes()
	{
		return $this->boxes;
	}

	/**
	 * Add a box
	 *
	 * @param TijsVerkoyenBpostBpostOrderBox $box
	 */
	public function addBox(TijsVerkoyenBpostBpostOrderBox $box)
	{
		$this->boxes[] = $box;
	}

	/**
	 * @param string $cost_center
	 */
	public function setCostCenter($cost_center)
	{
		$this->cost_center = $cost_center;
	}

	/**
	 * @return string
	 */
	public function getCostCenter()
	{
		return $this->cost_center;
	}

	/**
	 * @param array $lines
	 */
	public function setLines($lines)
	{
		$this->lines = $lines;
	}

	/**
	 * @return array
	 */
	public function getLines()
	{
		return $this->lines;
	}

	/**
	 * Add an order line
	 *
	 * @param TijsVerkoyenBpostBpostOrderLine $line
	 */
	public function addLine(TijsVerkoyenBpostBpostOrderLine $line)
	{
		$this->lines[] = $line;
	}

	/**
	 * @param string $reference
	 */
	public function setReference($reference)
	{
		$this->reference = $reference;
	}

	/**
	 * @return string
	 */
	public function getReference()
	{
		return $this->reference;
	}

	/**
	 * Return the object as an array for usage in the XML
	 *
	 * @param  \DOMDocument $document
	 * @param  string	   $account_id
	 * @return \DOMElement
	 */
	public function toXML(\DOMDocument $document, $account_id)
	{
		$order = $document->createElement(
			'tns:order'
		);
		$order->setAttribute(
			'xmlns:common',
			'http://schema.post.be/shm/deepintegration/v3/common'
		);
		$order->setAttribute(
			'xmlns:tns',
			'http://schema.post.be/shm/deepintegration/v3/'
		);
		$order->setAttribute(
			'xmlns',
			'http://schema.post.be/shm/deepintegration/v3/national'
		);
		$order->setAttribute(
			'xmlns:international',
			'http://schema.post.be/shm/deepintegration/v3/international'
		);
		$order->setAttribute(
			'xmlns:xsi',
			'http://www.w3.org/2001/XMLSchema-instance'
		);
		$order->setAttribute(
			'xsi:schemaLocation',
			'http://schema.post.be/shm/deepintegration/v3/'
		);

		$document->appendChild($order);

		$order->appendChild(
			$document->createElement(
				'tns:accountId',
				(string)$account_id
			)
		);

		if ($this->getReference() !== null)
			$order->appendChild(
				$document->createElement(
					'tns:reference',
					$this->getReference()
				)
			);
		if ($this->getCostCenter() !== null)
			$order->appendChild(
				$document->createElement(
					'tns:costCenter',
					$this->getCostCenter()
				)
			);

		$lines = $this->getLines();
		if (!empty($lines))
			foreach ($lines as $line)
				/** @var $line TijsVerkoyenBpostBpostOrderLine */
				$order->appendChild(
					$line->toXML($document, 'tns')
				);

		$boxes = $this->getBoxes();
		if (!empty($boxes))
			foreach ($boxes as $box)
				/** @var $box TijsVerkoyenBpostBpostOrderBox */
				$order->appendChild(
					$box->toXML($document, 'tns')
				);

		return $order;
	}

	/**
	 * @param  \SimpleXMLElement $xml
	 * @return TijsVerkoyenBpostOrder
	 * @throws TijsVerkoyenBpostException
	 */
	public static function createFromXML(\SimpleXMLElement $xml)
	{
		// @todo work with classmaps ...
		if (!isset($xml->reference))
			throw new TijsVerkoyenBpostException('No reference found.');

		$order = new TijsVerkoyenBpostOrder((string)$xml->reference);

		if (isset($xml->costCenter) && $xml->costCenter != '')
			$order->setCostCenter((string)$xml->costCenter);
		if (isset($xml->orderLine))
			foreach ($xml->orderLine as $order_line)
				$order->addLine(TijsVerkoyenBpostBpostOrderLine::createFromXML($order_line));
		if (isset($xml->box))
			foreach ($xml->box as $box)
				$order->addBox(TijsVerkoyenBpostBpostOrderBox::createFromXML($box));

		return $order;
	}
}
