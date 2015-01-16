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

	public function getProductCountries()
	{
		$acc_id = $this->getAccountId();
		if (!isset($acc_id) || empty($acc_id))
			throw new EontechModException('Invalid Account ID / Passphrase');

		$url = '/productconfig';
		$headers = array(
			'Accept: application/vnd.bpost.shm-productConfiguration-v3+XML'
		);
		
		$xml = $this->doCall(
			$url,
			null,
			$headers
		);

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
}