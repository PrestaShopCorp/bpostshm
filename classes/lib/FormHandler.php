<?php
/**
 * bPost Form handler class
 *
 * @author    Tijs Verkoyen <php-bpost@verkoyen.eu>
 * @version   3.0.0
 * @copyright Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license   BSD License
 */

class TijsVerkoyenBpostFormHandler
{
	/**
	 * bPost instance
	 *
	 * @var TijsVerkoyenBpostBpost
	 */
	private $bpost;

	/**
	 * The parameters
	 *
	 * @var array
	 */
	private $parameters = array();

	/**
	 * Create bPostFormHandler instance
	 *
	 * @param string $account_id
	 * @param string $pass_phrase
	 */
	public function __construct($account_id, $pass_phrase)
	{
		$this->bpost = new TijsVerkoyenBpostBpost($account_id, $pass_phrase);
	}

	/**
	 * Calculate the hash
	 *
	 * @return string
	 */
	private function getChecksum()
	{
		$keys_to_hash = array(
			'accountId',
			'action',
			'costCenter',
			'customerCountry',
			'deliveryMethodOverrides',
			'extraSecure',
			'orderReference',
			'orderWeight',
		);
		$base = 'accountId='.$this->bpost->getAccountId().'&';

		foreach ($keys_to_hash as $key)
			if (isset($this->parameters[$key]))
				$base .= $key.'='.$this->parameters[$key].'&';

		// add passphrase
		$base .= $this->bpost->getPassPhrase();

		// return the hash
		return hash('sha256', $base);
	}

	/**
	 * Get the parameters
	 *
	 * @param  bool  $form
	 * @param  bool  $include_checksum
	 * @return array
	 */
	public function getParameters($form = false, $include_checksum = true)
	{
		$return = $this->parameters;

		if ($form && isset($return['orderLine']))
		{
			foreach ($return['orderLine'] as $key => $value)
				$return['orderLine['.$key.']'] = $value;

			unset($return['orderLine']);
		}

		if ($include_checksum)
		{
			$return['accountId'] = $this->bpost->getAccountId();
			$return['checksum'] = $this->getChecksum();
		}

		return $return;
	}

	/**
	 * Set a parameter
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @throws TijsVerkoyenBpostException
	 */
	public function setParameter($key, $value)
	{
		switch ((string)$key)
		{
			// limited values
			case 'action':
			case 'lang':
				$allowed_values = array();
				$allowed_values['action'] = array('START', 'CONFIRM');
				$allowed_values['lang'] = array('NL', 'FR', 'EN', 'DE', 'Default');

				if (!in_array($value, $allowed_values[$key]))
					throw new TijsVerkoyenBpostException(
						'Invalid value ('.$value.') for '.$key.', allowed values are: '.implode(', ', $allowed_values[$key]).'.'
					);
				$this->parameters[$key] = $value;
				break;

			// maximum 2 chars
			case 'customerCountry':
				if (mb_strlen($value) > 2)
					throw new TijsVerkoyenBpostException(
						'Invalid length for '.$key.', maximum is 2.'
					);
				$this->parameters[$key] = (string)$value;
				break;

			// maximum 8 chars
			case 'customerStreetNumber':
			case 'customerBox':
				if (mb_strlen($value) > 8)
					throw new TijsVerkoyenBpostException(
						'Invalid length for '.$key.', maximum is 8.'
					);
				$this->parameters[$key] = (string)$value;
				break;

			// maximum 20 chars
			case 'customerPhoneNumber':
				if (mb_strlen($value) > 20)
					throw new TijsVerkoyenBpostException(
						'Invalid length for '.$key.', maximum is 20.'
					);
				$this->parameters[$key] = (string)$value;
				break;

			// maximum 32 chars
			case 'customerPostalCode':
				if (mb_strlen($value) > 32)
					throw new TijsVerkoyenBpostException(
						'Invalid length for '.$key.', maximum is 32.'
					);
				$this->parameters[$key] = (string)$value;
				break;

			// maximum 40 chars
			case 'customerFirstName':
			case 'customerLastName':
			case 'customerCompany':
			case 'customerStreet':
			case 'customerCity':
				if (mb_strlen($value) > 40)
					throw new TijsVerkoyenBpostException(
						'Invalid length for '.$key.', maximum is 40.'
					);
				$this->parameters[$key] = (string)$value;
				break;

			// maximum 50 chars
			case 'orderReference':
			case 'costCenter':
			case 'customerEmail':
				if (mb_strlen($value) > 50)
					throw new TijsVerkoyenBpostException(
						'Invalid length for '.$key.', maximum is 50.'
					);
				$this->parameters[$key] = (string)$value;
				break;

			// integers
			case 'orderTotalPrice':
			case 'orderWeight':
				$this->parameters[$key] = (int)$value;
				break;

			// array
			case 'orderLine':
				if (!isset($this->parameters[$key]))
					$this->parameters[$key] = array();
				$this->parameters[$key][] = $value;
				break;

			// unknown
			case 'deliveryMethodOverrides':
			case 'extra':
			case 'extraSecure':
			case 'confirmUrl':
			case 'cancelUrl':
			case 'errorUrl':
			default:
				$this->parameters[$key] = $value;
		}
	}
}
