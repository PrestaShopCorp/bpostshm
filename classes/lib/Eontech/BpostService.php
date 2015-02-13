<?php
/**
 * Bpost class
 *
 * @author    Serge <serge@stigmi.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Eontech.net All rights reserved.
 * @license   BSD License
 */

class EontechBpostService extends EontechModBpost
{

	/**
	 * XML errors
	 *
	 * @var array
	 */
	private $xml_errors = array();

	public function getProductCountries()
	{
		$msg_invalid = 'Invalid Account ID / Passphrase';
		$acc_id = $this->getAccountId();
		if (empty($acc_id))
			throw new EontechModException($msg_invalid);

		$url = '/productconfig';
		$headers = array(
			'Accept: application/vnd.bpost.shm-productConfiguration-v3+XML'
		);

		$prev_use = libxml_use_internal_errors(true);
		try {
			$xml = $this->doCall(
				$url,
				null,
				$headers
			);

		} catch (EontechModException $e) {
			libxml_clear_errors();
			libxml_use_internal_errors($prev_use);
			if (401 === (int)$e->getCode())
				throw new EontechModException($msg_invalid);
			else
				throw $e;
		}

		libxml_clear_errors();
		libxml_use_internal_errors($prev_use);

		if (!isset($xml->deliveryMethod))
			throw new EontechModException('No suitable delivery method');

		$product_countries = false;
		foreach ($xml->deliveryMethod as $dm)
			foreach ($dm->product as $product)
				if ('bpack World' === mb_substr((string)$product->attributes()->name, 0, 11)
					&& isset($product->price))
				{
					$product_countries = array();
					foreach ($product->price as $price)
						$product_countries[] = (string)$price->attributes()->countryIso2Code;

					break;
				}

		return empty($product_countries) ? false : $product_countries;
	}

	public function getXmlErrors()
	{
		if (is_array($this->xml_errors) && count($this->xml_errors))
			return $this->xml_errors;

		return false;
	}
}