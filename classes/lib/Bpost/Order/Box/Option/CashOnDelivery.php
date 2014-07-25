<?php
/**
 * bPost CashOnDelivery class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

namespace TijsVerkoyen\Bpost\Bpost\Order\Box\Option;

class CashOnDelivery extends Option
{
	/**
	 * @var float
	 */
	private $amount;

	/**
	 * @var string
	 */
	private $iban;

	/**
	 * @var string
	 */
	private $bic;

	/**
	 * @param float $amount
	 */
	public function setAmount($amount)
	{
		$this->amount = $amount;
	}

	/**
	 * @return float
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * @param string $bic
	 */
	public function setBic($bic)
	{
		$this->bic = $bic;
	}

	/**
	 * @return string
	 */
	public function getBic()
	{
		return $this->bic;
	}

	/**
	 * @param string $iban
	 */
	public function setIban($iban)
	{
		$this->iban = $iban;
	}

	/**
	 * @return string
	 */
	public function getIban()
	{
		return $this->iban;
	}

	/**
	 * @param float  $amount
	 * @param string $iban
	 * @param string $bic
	 */
	public function __construct($amount, $iban, $bic)
	{
		$this->setAmount($amount);
		$this->setIban($iban);
		$this->setBic($bic);
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
		$tag_name = 'cod';
		if ($prefix !== null)
			$tag_name = $prefix.':'.$tag_name;

		$cod = $document->createElement($tag_name);

		if ($this->getAmount() !== null)
		{
			$tag_name = 'codAmount';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$cod->appendChild(
				$document->createElement(
					$tag_name,
					$this->getAmount()
				)
			);
		}
		if ($this->getIban() !== null)
		{
			$tag_name = 'iban';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$cod->appendChild(
				$document->createElement(
					$tag_name,
					$this->getIban()
				)
			);
		}
		if ($this->getBic() !== null)
		{
			$tag_name = 'bic';
			if ($prefix !== null)
				$tag_name = $prefix.':'.$tag_name;
			$cod->appendChild(
				$document->createElement(
					$tag_name,
					$this->getBic()
				)
			);
		}

		return $cod;
	}
}
