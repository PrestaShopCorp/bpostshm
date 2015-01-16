<?php
/**
* Barcode Collection manager class Prestashop module helper
*  
* @author    Serge <serge@stigmi.eu>
* @version   0.5.0
* @copyright Copyright (c), Eontech.net. All rights reserved.
* @license   BSD License
*/

if (!defined('_PS_VERSION_'))
	exit;

class EontechBarcodeCollection extends EontechBaseObject
{
	const ERR_INITIALIZE = 3;
	const ERR_EMPTY = 2;

	const TYPE_NORMAL = 0;
	const TYPE_RETURN = 1;
	const TYPE_INTL = 2;

	private $_barcodes = array();

	public function __construct($barcodes = null, $raise_exceptions = false)
	{
		parent::__construct(true, $raise_exceptions);
		$this->resetBarcodes();
		$this->addBarcodes($barcodes);
	}

	public function getBarcodes()
	{
		return $this->_barcodes;
	}

	public function addBarcodes($barcodes = null)
	{
		if (!isset($barcodes) || !is_array($barcodes) || empty($barcodes))
			return;

		foreach ($barcodes as $barcode)
		{
			$type = (int)$this->getBarcodeType($barcode);
			$this->_barcodes[] = array($type => $barcode);
		}
	}

	public function count()
	{
		return (int)count($this->_barcodes);
	}

	public function isEmpty()
	{
		return (bool)(0 === $this->count());
	}

	public function getNext($is_return = false, $is_intl = false)
	{
		$return = '';

		if (!$this->isEmpty())
		{	
			$req_type = ($is_return ? self::TYPE_RETURN : self::TYPE_NORMAL) + ($is_intl ? self::TYPE_INTL : self::TYPE_NORMAL);
			foreach ($this->_barcodes as $key => $barcode)
			{
				$type = key($barcode);
				if ($req_type == $type)
				{
					$return = $barcode[$type];
					unset($this->_barcodes[$key]);
					break;
				}
			}
		}

		return $return;
	}

	public function getNextAutoReturn($is_intl = false)
	{
		$result = array(
			$this->getNext(false, $is_intl),
			$this->getNext(true, $is_intl),
			);

		return $result;
	}

	protected function getBarcodeType($barcode)
	{
		$type = self::TYPE_NORMAL;
		// last 3 digits of 24 digits:
		// 050 => normal + return
		// 134 => intl + return
		// 13 digits => normal + intl
		$len = mb_strlen((string)$barcode);
		if (13 == $len)
			$type |= self::TYPE_INTL;
		elseif (24 == $len)
			switch(substr($barcode, -3))
			{
				case '134':
					$type |= self::TYPE_INTL;
					// fall through
				case '050':
					$type |= self::TYPE_RETURN;
					break;
			}

		return $type;
	}

	protected function resetBarcodes()
	{
		$this->resetError();
		$this->_barcodes = array();
	}

	protected function setError($msg, $severity = self::ERR_INITIALIZE)
	{
		parent::setError($msg, $severity);
	}
}