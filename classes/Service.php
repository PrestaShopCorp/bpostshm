<?php
/**
 * 2014 Stigmi
 *
 * bpost Shipping Manager
 *
 * Allow your customers to choose their preferrred delivery method: delivery at home or the office, at a pick-up location or in a bpack 24/7 parcel
 * machine.
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once(_PS_CLASS_DIR_.'Tools.php');
require_once(_PS_MODULE_DIR_.'bpostshm/bpostshm.php');

class Service
{
	private static $_slugs_international = array(
		'bpack World Express Pro',
		'bpack World Business',
		);

	const GEO6_PARTNER = 999999;
	const GEO6_APP_ID = '';
	const BPACK247_ID = 'test@bpost.be';
	const BPACK247_PASS = 'test';
	const GMAPS_API_KEY = 'AIzaSyAa4S8Br_5of6Jb_Gjv1WLldkobgExB2KY';

	public static $cache = array();

	/**
	 * @var Service
	 */
	protected static $instance;
	private $context;
	private $geo6;
	public $bpost;

	/**
	 * @param Context $context
	 */
	public function __construct(Context $context)
	{
		require_once(_PS_MODULE_DIR_.'bpostshm/classes/Autoloader.php');
		if (Service::isPrestashop15plus())
			spl_autoload_register(array(Autoloader::getInstance(), 'load'));
		else
			spl_autoload_register(array(Autoloader::getInstance(), 'loadPS14'));

		$this->context = $context;

		$this->bpost = new EontechBpostService(
			Configuration::get('BPOST_ACCOUNT_ID'),
			Configuration::get('BPOST_ACCOUNT_PASSPHRASE')
		);
		$this->geo6 = new EontechModGeo6(
			self::GEO6_PARTNER,
			self::GEO6_APP_ID
		);
		$this->module = new BpostShm();
	}

	/**
	 * @param Context $context
	 * @return Service
	 */
	public static function getInstance(Context $context = null)
	{
		if (!Service::$instance)
		{
			if (is_null($context))
				$context = Context::getContext();
			self::$instance = new Service($context);
		}

		return Service::$instance;
	}

	public static function isPrestashop155plus()
	{
		return version_compare(_PS_VERSION_, '1.5.5.0', '>=');
	}

	public static function isPrestashop15plus()
	{
		return version_compare(_PS_VERSION_, '1.5', '>=');
	}

	public static function isPrestashop16plus()
	{
		return version_compare(_PS_VERSION_, '1.6', '>=');
	}

	public static function updateGlobalValue($key, $value)
	{
		if (self::isPrestashop15plus())
			return Configuration::updateGlobalValue($key, $value);
		else
			return Configuration::updateValue($key, $value);
	}

	public static function getOrderIDFromReference($reference = '')
	{
		$return = false;

		$ref_parts = explode('_', $reference);
		if (3 === count($ref_parts))
			$return = (int)$ref_parts[1];

		return $return;
	}

	/**
	 * Mimic 1.5+ order reference field for 1.4
	 *
	 * @return String
	 */
	public static function generateReference()
	{
		return Tools::strtoupper(Tools::passwdGen(9, 'NO_NUMERIC'));
	}

	public static function getActualShm($db_shm)
	{
		// actual shipping method in 1st 3-bits
		return $db_shm & 7;
	}

	public static function isInternational($db_shm)
	{
		return BpostShm::SHIPPING_METHOD_AT_INTL == (int)$db_shm;
	}

	public static function isAtHome($shm)
	{
		$is_athome = $shm & BpostShm::SHIPPING_METHOD_AT_HOME;
		return (bool)$is_athome;
	}

	public static function getBpostring($str, $max = false)
	{
		$pattern = '/[^\pL0-9,-_\.\s\'\(\)\&]/u';
		$rpl = '-';
		$str = preg_replace($pattern, $rpl, trim($str));
		$str = str_replace(array('/', '\\'), $rpl, $str);
		if (false === strpos($str, '&amp;'))
			$str = str_replace('&', '&amp;', $str);

		// Tools:: version fails miserably, so don't even...
		// return Tools::substr($str, 0, $max);
		return mb_substr($str, 0, $max ? $max : mb_strlen($str));
	}

	public function getWeightGrams($weight = 0)
	{
		$weight = (empty($weight) || !is_numeric($weight)) ? 1.0 : (float)$weight;
		$weight_unit = Tools::strtolower(Configuration::get('PS_WEIGHT_UNIT'));
		switch ($weight_unit)
		{
			case 'kg':
				$weight *= 1000;
				break;

			case 'g':
				break;

			case 'lbs':
			case 'lb':
				$weight *= 453.592;
				break;

			case 'oz':
				$weight *= 28.34952;
				break;

			default:
				$weight = 1000;
				break;
		}
		$weight = (int)round($weight, 0, PHP_ROUND_HALF_UP);

		return empty($weight) ? 1 : $weight;
	}

	/**
	 * @param array $search_params
	 * @param int $type
	 * @return array
	 */
	public function getNearestServicePoint($search_params = array(), $type = 3)
	{
		$service_points = array();

		$search_params = array_merge(array(
				'street' 	=> '',
				'nr' 		=> '',
				'zone'		=> '',
			), $search_params);

		try {
			if ($response = $this->geo6->getNearestServicePoint($search_params['street'], $search_params['nr'], $search_params['zone'],
				$this->context->language->iso_code, $type))
			{
				foreach ($response as $row)
				{
					$service_points['coords'][] = array(
						$row['poi']->getLatitude(),
						$row['poi']->getLongitude(),
					);
					$service_points['list'][] = array(
						'id' 			=> $row['poi']->getId(),
						'type' 			=> $row['poi']->getType(),
						'office' 		=> $row['poi']->getOffice(),
						'street' 		=> $row['poi']->getstreet(),
						'nr' 			=> $row['poi']->getNr(),
						'zip' 			=> $row['poi']->getZip(),
						'city' 			=> $row['poi']->getCity(),
					);
					$service_points['distance'] = $row['distance'];
				}
			}
		} catch (EontechModException $e) {
			$service_points = array();
		}

		return $service_points;
	}

	/**
	 * @param int $service_point_id
	 * @param int $type
	 * @return array
	 */
	public function getServicePointDetails($service_point_id = 0, $type = 3)
	{
		$service_point_details = array();
		try {
			if ($poi = $this->geo6->getServicePointDetails($service_point_id, $this->context->language->iso_code, $type))
			{
				$service_point_details['id'] 		= $poi->getId();
				$service_point_details['office'] 	= $poi->getOffice();
				$service_point_details['street'] 	= $poi->getStreet();
				$service_point_details['nr'] 		= $poi->getNr();
				$service_point_details['zip'] 		= $poi->getZip();
				$service_point_details['city'] 		= $poi->getCity();
			}
		} catch (EontechModException $e) {
			$service_point_details = array();
			if (2 === $type)
				$service_point_details = $this->getServicePointDetails($service_point_id, 1);

		}

		return $service_point_details;
	}

	/**
	 * @param int $service_point_id
	 * @param int $type
	 * @return array
	 */
	public function getServicePointHours($service_point_id = 0, $type = 3)
	{
		$service_point_hours = array();

		try {
			if ($response = $this->geo6->getServicePointDetails($service_point_id, $this->context->language->iso_code, $type))
				if ($service_point_days = $response->getHours())
					foreach ($service_point_days as $day)
						$service_point_hours[$day->getDay()] = array(
							'am_open' => $day->getAmOpen(),
							'am_close' => $day->getAmClose(),
							'pm_open' => $day->getPmOpen(),
							'pm_close' => $day->getPmClose(),
						);
		} catch (EontechModException $e) {
			$service_point_hours = array();
		}

		return $service_point_hours;
	}

	/**
	 * extract number, street and line2 from address fields
	 * @author Serge <serge@stigmi.eu>
	 * @param  array $address
	 * @return array $address
	 */
	public function getAddressStreetNr($address = '')
	{
		if (empty($address) || !is_array($address))
			return false;

		$line2 = $address['line2'];
		preg_match('#([0-9]+)?[, ]*([\pL&;\'\. -]+)[, ]*([0-9]+[a-z]*)?[, ]*(.*)?#iu', $address['street'], $matches);
		if (!empty($matches[1]))
			$nr = $matches[1];
		elseif (!empty($matches[3]))
			$nr = $matches[3];
		elseif (!empty($line2) && is_numeric($line2))
		{
			$nr = $line2;
			$line2 = '';
		}

		$address['nr'] = $nr;
		$address['line2'] = !empty($matches[4]) ? $matches[4].(!empty($line2) ? ', '.$line2 : '') : $line2;
		if (!empty($matches[2]))
			$address['street'] = $matches[2];

		return $address;
	}

	/**
	 * Rearrange address fields depending on Address2! because of stingy WS 40 char max fields
	 * @author Serge <serge@stigmi.eu>
	 * @param  array $person shop or client
	 * @return array Bpost formatted shipper
	 */
	protected function getBpostShipper($person = '')
	{
		if (empty($person))
			return false;

		$address = array(
			'nr' => ',',
			'street' => $person['address1'],
			'line2' => $person['address2'],
			);

		$iso_code = Tools::strtoupper(Country::getIsoById($person['id_country']));
		// if ('BE' === $iso_code)
		// 	$address = $this->getAddressStreetNr($address);

		$shipper = array(
			'name' => $person['name'],
			'company' => isset($person['company']) ? $person['company'] : '',
			'number' => $address['nr'],
			'street' => $address['street'],
			'line2' => $address['line2'],
			'postcode' => $person['postcode'],
			'locality' => $person['city'],
			'countrycode' => $iso_code,
			'phone' => $person['phone'],
			'email' => $person['email'],
			);

		return $shipper;
	}

	/**
	 * @param Order $ps_order
	 * @param bool $is_retour
	 * @param int $shm_at_home bpost address field limits require differences for @home !
	 * @return array 'sender' & 'receiver' + formatted 'recipient'
	 */
	public function getReceiverAndSender($ps_order, $is_retour = false, $shm_at_home = false)
	{
		$customer = new Customer((int)$ps_order->id_customer);
		$delivery_address = new Address($ps_order->id_address_delivery, $this->context->language->id);
		// $invoice_address = new Address($ps_order->id_address_invoice, $this->context->language->id);
		$company = self::getBpostring($delivery_address->company);
		$client_line1 = self::getBpostring($delivery_address->address1);
		$client_line2 = self::getBpostring($delivery_address->address2);

		$shippers = array(
			'client' => array(
				'address1' 	=> $client_line1,
				'address2' 	=> $client_line2,
				'city' 		=> $delivery_address->city,
				'email'		=> $customer->email,
				'id_country'=> $delivery_address->id_country,
				'name'		=> $delivery_address->firstname.' '.$delivery_address->lastname,
				'phone'		=> !empty($delivery_address->phone) ? $delivery_address->phone : $delivery_address->phone_mobile,
				'postcode' 	=> $delivery_address->postcode,
			),
			'shop' =>  array(
				'address1' 	=> Configuration::get('PS_SHOP_ADDR1'),
				'address2' 	=> Configuration::get('PS_SHOP_ADDR2'),
				'city' 		=> Configuration::get('PS_SHOP_CITY'),
				'email' 	=> Configuration::get('PS_SHOP_EMAIL'),
				'id_country'=> Configuration::get('PS_SHOP_COUNTRY_ID'),
				'name'		=> self::getBpostring(Configuration::get('PS_SHOP_NAME')),
				'phone'		=> Configuration::get('PS_SHOP_PHONE'),
				'postcode' 	=> Configuration::get('PS_SHOP_CODE'),
			),
		);

		$client = $this->getBpostShipper($shippers['client']);
		$recipient = $client['name'];
		if (!empty($client['line2']) && (bool)$shm_at_home)
		{
			$company = !empty($company) ? ' ('.$company.')' : '';
			$company = $client['name'].$company;
			$client['name'] = $client['line2'];
			$recipient = $company;
		}
		$client['company'] = $company;
		$shop = $this->getBpostShipper($shippers['shop']);

		$sender = $shop;
		$receiver = $client;
		if ($is_retour)
		{
			$sender = $client;
			$receiver = $shop;
		}

		// sender
		$address = new EontechModBpostOrderAddress();
		$address->setNumber(Tools::substr($sender['number'], 0, 8));
		$address->setStreetName(self::getBpostring($sender['street'], 40));
		$address->setPostalCode(self::getBpostring($sender['postcode'], 32));
		$address->setLocality(self::getBpostring($sender['locality'], 40));
		$address->setCountryCode($sender['countrycode']);

		$bpost_sender = new EontechModBpostOrderSender();
		$bpost_sender->setAddress($address);
		$bpost_sender->setName(self::getBpostring($sender['name'], 40));
		if (!empty($sender['company']))
			$bpost_sender->setCompany(self::getBpostring($sender['company'], 40));
		$sender_phone = Tools::substr($sender['phone'], 0, 20);
		if (!(empty($sender_phone)))
			$bpost_sender->setPhoneNumber($sender_phone);
		$bpost_sender->setEmailAddress(Tools::substr($sender['email'], 0, 50));

		// receiver
		$address = new EontechModBpostOrderAddress();
		$address->setNumber(Tools::substr($receiver['number'], 0, 8));
		$address->setStreetName(self::getBpostring($receiver['street'], 40));
		$address->setPostalCode(self::getBpostring($receiver['postcode'], 32));
		$address->setLocality(self::getBpostring($receiver['locality'], 40));
		$address->setCountryCode($receiver['countrycode']);

		$bpost_receiver = new EontechModBpostOrderReceiver();
		$bpost_receiver->setAddress($address);
		$bpost_receiver->setName(self::getBpostring($receiver['name'], 40));
		if (!empty($receiver['company']))
			$bpost_receiver->setCompany(self::getBpostring($receiver['company'], 40));
		$receiver_phone = Tools::substr($receiver['phone'], 0, 20);
		if (!(empty($receiver_phone)))
			$bpost_receiver->setPhoneNumber($receiver_phone);
		$bpost_receiver->setEmailAddress(Tools::substr($receiver['email'], 0, 50));

		// recipient continued (* only when not retour *)
		if (false === $is_retour)
		{
			$nb = $address->getNumber();
			$country_code = Tools::strtoupper($address->getCountryCode());
			// $nb_part = is_numeric($nb) ? ' '.$nb : '';
			$nb_part = 'BE' === $country_code && ',' !== $nb ? ' '.$nb : '';
			$street2 = empty($client['line2']) ? '' : ', '.$client['line2'];
			$recipient .= ', '.$address->getStreetName().$nb_part.$street2
				.' '.$address->getPostalCode().' '.$address->getLocality().' ('.$country_code.')';
		}

		return array(
			'receiver' => $bpost_receiver,
			'sender' => $bpost_sender,
			'recipient' => html_entity_decode($recipient),
		);
	}

	public function getReceiverAndSenderProperly($ps_order, $is_retour = false)
	{
		$customer = new Customer((int)$ps_order->id_customer);
		$delivery_address = new Address($ps_order->id_address_delivery, $this->context->language->id);
		$invoice_address = new Address($ps_order->id_address_invoice, $this->context->language->id);

		$name_pattern = '/[^\pL0-9-_\s]+/u';
		$shippers = array(
			'client' => array(
				'address1' 	=> $delivery_address->address1,
				'address2' 	=> $delivery_address->address2,
				'city' 		=> $delivery_address->city,
				'email'		=> $customer->email,
				'id_country'=> $delivery_address->id_country,
				'name'		=> $invoice_address->firstname.' '.$invoice_address->lastname,
				'phone'		=> !empty($delivery_address->phone) ? $delivery_address->phone : $delivery_address->phone_mobile,
				'postcode' 	=> $delivery_address->postcode,
			),
			'shop' =>  array(
				'address1' 	=> Configuration::get('PS_SHOP_ADDR1'),
				'address2' 	=> Configuration::get('PS_SHOP_ADDR2'),
				'city' 		=> Configuration::get('PS_SHOP_CITY'),
				'email' 	=> Configuration::get('PS_SHOP_EMAIL'),
				'id_country'=> Configuration::get('PS_SHOP_COUNTRY_ID'),
				'name'		=> preg_replace($name_pattern, '', (string)Configuration::get('PS_SHOP_NAME')),
				'phone'		=> Configuration::get('PS_SHOP_PHONE'),
				'postcode' 	=> trim(Configuration::get('PS_SHOP_CODE')), // todo something better than trim
			),
		);

		if (!empty($delivery_address->company))
			$shippers['client']['company'] = $delivery_address->company;

		$sender = $shippers['shop'];
		$receiver = $shippers['client'];
		if ($is_retour)
		{
			$sender = $shippers['client'];
			$receiver = $shippers['shop'];
		}

		// create $bpost_sender
		//preg_match('#([0-9]+)?[, ]*([\p{L}a-zA-Z -]+)[, ]*([0-9]+)?#iu', $sender['address1'], $matches);
		preg_match('#([0-9]+)?[, ]*([\p{L}a-zA-Z -\']+)[, ]*([0-9]+)?#iu', $sender['address1'], $matches);
		if (!empty($matches[1]) && is_numeric($matches[1]))
			$nr = $matches[1];
		elseif (!empty($matches[3]) && is_numeric($matches[3]))
			$nr = $matches[3];
		else
			$nr = (!empty($sender['address2']) && is_numeric($sender['address2']) ? $sender['address2'] : 0);
		$street = !empty($matches[2]) ? $matches[2] : $sender['address1'];

		$address = new EontechModBpostOrderAddress();
		$address->setNumber(Tools::substr($nr, 0, 8));
		$address->setStreetName(Tools::substr($street.(!empty($sender['address2']) && !is_numeric($sender['address2'])
			? ' '.$sender['address2'] : ''), 0, 40));
		//$address->setPostalCode(Tools::substr((int)$sender['postcode'], 0, 32));
		// int ?? (TW14 2EF) postcode in England is not an (int)
		$address->setPostalCode(Tools::substr($sender['postcode'], 0, 32));
		$address->setLocality(Tools::substr($sender['city'], 0, 40));
		$address->setCountryCode(Tools::strtoupper(Country::getIsoById($sender['id_country'])));

		$bpost_sender = new EontechModBpostOrderSender();
		$bpost_sender->setAddress($address);
		$bpost_sender->setName(Tools::substr($sender['name'], 0, 40));
		if (!empty($sender['company']))
			$bpost_sender->setCompany(Tools::substr($sender['company'], 0, 40));
		$sender_phone = Tools::substr($sender['phone'], 0, 20);
		if (!(empty($sender_phone)))
			$bpost_sender->setPhoneNumber($sender_phone);
		$bpost_sender->setEmailAddress(Tools::substr($sender['email'], 0, 50));

		// create $bpost_receiver
		//preg_match('#([0-9]+)?[, ]*([\p{L}a-zA-Z -]+)[, ]*([0-9]+)?#iu', $receiver['address1'], $matches);
		preg_match('#([0-9]+)?[, ]*([\p{L}a-zA-Z -\']+)[, ]*([0-9]+)?#iu', $receiver['address1'], $matches);
		if (!empty($matches[1]) && is_numeric($matches[1]))
			$nr = $matches[1];
		elseif (!empty($matches[3]) && is_numeric($matches[3]))
			$nr = $matches[3];
		else
			$nr = (!empty($receiver['address2']) && is_numeric($receiver['address2']) ? $receiver['address2'] : 0);
		$street = !empty($matches[2]) ? $matches[2] : $receiver['address1'];

		$address = new EontechModBpostOrderAddress();
		$address->setNumber(Tools::substr($nr, 0, 8));
		$address->setStreetName(Tools::substr($street.(!empty($receiver['address2']) && !is_numeric($receiver['address2'])
			? ' '.$receiver['address2'] : ''), 0, 40));
		//$address->setPostalCode(Tools::substr((int)$receiver['postcode'], 0, 32));
		$address->setPostalCode(Tools::substr($receiver['postcode'], 0, 32));
		$address->setLocality(Tools::substr($receiver['city'], 0, 40));
		$address->setCountryCode(Tools::strtoupper(Country::getIsoById($receiver['id_country'])));

		$bpost_receiver = new EontechModBpostOrderReceiver();
		$bpost_receiver->setAddress($address);
		$bpost_receiver->setName(Tools::substr($receiver['name'], 0, 40));
		if (!empty($receiver['company']))
			$bpost_receiver->setCompany(Tools::substr($receiver['company'], 0, 40));
		$receiver_phone = Tools::substr($receiver['phone'], 0, 20);
		if (!(empty($receiver_phone)))
			$bpost_receiver->setPhoneNumber($receiver_phone);
		//$bpost_receiver->setPhoneNumber(Tools::substr($receiver['phone'], 0, 20));
		$bpost_receiver->setEmailAddress(Tools::substr($receiver['email'], 0, 50));

		return array(
			'receiver' => $bpost_receiver,
			'sender' => $bpost_sender,
		);
	}

	/**
	 * @author Serge <serge@stigmi.eu>
	 * @param int $id_order
	 * @return boolean
	 */
	public function prepareBpostOrder($id_order = 0)
	{
		$response = true;

		if (empty($id_order) || !is_numeric($id_order))
			return false;

		$weight = 0;
		$ps_order = new Order((int)$id_order);

		// create a unique reference
		$ref = self::isPrestashop15plus() ? $ps_order->reference : self::generateReference();
		$reference = Configuration::get('BPOST_ACCOUNT_ID').'_'.Tools::substr($ps_order->id, 0, 42).'_'.$ref;

		$shm = $this->module->getShmFromCarrierID($ps_order->id_carrier);
		$delivery_method = $this->module->shipping_methods[$shm]['slug'];
		switch ($shm)
		{
			case BpostShm::SHIPPING_METHOD_AT_HOME:
				if ($address = Address::getCountryAndState((int)$ps_order->id_address_delivery))
				{
					$country = new Country((int)$address['id_country']);
					if ('BE' != $country->iso_code)
					{
						// $delivery_method = '@international';
						$shm = BpostShm::SHIPPING_METHOD_AT_INTL;
						$options_list = $this->getDeliveryOptionsList('intl', ':');
						$delivery_method = $this->getInternationalSlug(true).$options_list;
					}
					else
						// Belgian address
						$delivery_method .= $this->getDeliveryOptionsList('home', ':');
				}
				break;

			case BpostShm::SHIPPING_METHOD_AT_SHOP:
				$delivery_method .= $this->getDeliveryOptionsList('bpost', ':');
				break;

			case BpostShm::SHIPPING_METHOD_AT_24_7:
				// $delivery_method .= $this->getDeliveryOptionsList('247', ':');
				$delivery_method = 'Parcel locker'.$this->getDeliveryOptionsList('247', ':');
				break;

		}

		if ((bool)Configuration::get('BPOST_USE_PS_LABELS'))
		{
			// Labels managed are within Prestashop
			$shippers = $this->getReceiverAndSender($ps_order, false, self::isAtHome($shm));
			$recipient = $shippers['recipient'];

			$order_bpost = new PsOrderBpost();
			$order_bpost->reference = (string)$reference;
			$order_bpost->recipient = (string)$recipient;
			$order_bpost->shm = (int)$shm;
			$order_bpost->delivery_method = (string)$delivery_method;
			$response = $response && $order_bpost->save();
			$response = $response && $order_bpost->addLabel();
		}
		else
		{
			// Send order for SHM only processing
			$bpost_order = new EontechModBpostOrder($reference);
			// $bpost_order->setCostCenter('PrestaShop_'._PS_VERSION_);
			// $bpost_order->setCostCenter('Cost Center');

			// add product lines
			if ($products = $ps_order->getProducts())
				foreach ($products as $product)
				{
					$product_name = self::getBpostring($product['product_name']);
					$line = new EontechModBpostOrderLine($product_name, $product['product_quantity']);
					$bpost_order->addLine($line);
					$weight += $product['product_weight'];
				}
			$weight = (int)$this->getWeightGrams($weight);

			$box = $this->createBox($reference, $shm, $ps_order, $weight);
			$bpost_order->addBox($box);

			try {
				$response = $this->bpost->createOrReplaceOrder($bpost_order);

			} catch (Exception $e) {
				self::logError('prepareBpostOrder Ref: '.$reference, $e->getMessage(), $e->getCode(), 'Order', $id_order);
				$response = false;

			}
		}

		return $response;
	}

	/**
	 * @param string $reference Bpost order reference
	 * @param int $db_shm Shipping method (regular 3 + 4th bit for international)
	 * @param Order $ps_order Prestashop order
	 * @param int $weight
	 * @param bool $is_retour if True create a retour box
	 * @return EontechModBpostOrderBox
	 */
	public function createBox($reference = '',
		$db_shm = 0,
		$ps_order = null,
		$weight = 1000,
		$is_retour = false)
	{
		if (empty($reference) || empty($db_shm) || !isset($ps_order))
			return false;

		$shipping_method = (int)self::getActualShm($db_shm);
		$has_service_point = !self::isAtHome($shipping_method);
		if ($has_service_point)
		{
			$cart = PsCartBpost::getByPsCartID((int)$ps_order->id_cart);
			$service_point_id = (int)$cart->service_point_id;
			$sp_type = (int)$cart->sp_type;

			if ($is_retour)
				// effective $shipping_method if retour is on is always @home!
				$shipping_method = (int)BpostShm::SHIPPING_METHOD_AT_HOME;
		}

		$shippers = $this->getReceiverAndSender($ps_order, $is_retour, !$has_service_point);
		$sender = $shippers['sender'];
		$receiver = $shippers['receiver'];

		$box = new EontechModBpostOrderBox();
		$box->setStatus('OPEN');
		$box->setSender($sender);

		switch ($shipping_method)
		{
			case BpostShm::SHIPPING_METHOD_AT_HOME:
				if (self::isInternational($db_shm))
				{
					// @International
					$customs_info = new EontechModBpostOrderBoxCustomsinfoCustomsInfo();
					$customs_info->setParcelValue((float)$ps_order->total_paid * 100);
					$customs_info->setContentDescription(Tools::substr('ORDER '.Configuration::get('PS_SHOP_NAME'), 0, 50));
					$customs_info->setShipmentType('OTHER');
					$customs_info->setParcelReturnInstructions('RTS');
					$customs_info->setPrivateAddress(false);

					$international = new EontechModBpostOrderBoxInternational();
					$international->setReceiver($receiver);
					$international->setParcelWeight($weight);
					$international->setCustomsInfo($customs_info);

					if ($is_retour)
						$international->setProduct('bpack World Easy Return');
					else
					{
						$international->setProduct($this->getInternationalSlug());
						$delivery_options = $this->getDeliveryBoxOptions('intl');
						foreach ($delivery_options as $option)
							$international->addOption($option);
					}

					$box->setInternationalBox($international);
				}
				else
				{
					// @Home
					$at_home = new EontechModBpostOrderBoxAtHome();
					$at_home->setReceiver($receiver);
					$at_home->setWeight($weight);
					if ($is_retour)
						$at_home->setProduct('bpack Easy Retour');
					else
					{
						$at_home->setProduct('bpack 24h Pro');

						$delivery_options = $this->getDeliveryBoxOptions('home');
						foreach ($delivery_options as $option)
							$at_home->addOption($option);
					}

					$box->setNationalBox($at_home);
				}
				break;

			case BpostShm::SHIPPING_METHOD_AT_SHOP:
				// @Bpost
				// Never retour
				$service_point = $this->getServicePointDetails($service_point_id, $sp_type);
				$pugo_address = new EontechModBpostOrderPugoAddress(
					$service_point['street'],
					$service_point['nr'],
					null,
					$service_point['zip'],
					$service_point['city'],
					'BE'
				);

				$at_bpost = new EontechModBpostOrderBoxAtBpost();
				$at_bpost->setPugoId(sprintf('%06s', $service_point_id));
				$at_bpost->setPugoName(Tools::substr($service_point['office'], 0, 40));
				$at_bpost->setPugoAddress($pugo_address);
				$at_bpost->setReceiverName(Tools::substr($receiver->getName(), 0, 40));
				$at_bpost->setReceiverCompany(Tools::substr($receiver->getCompany(), 0, 40));

				// language must default to EN if not in allowed values
				$lang_iso = $this->context->language->iso_code;
				$lang_iso = in_array(Tools::strtoupper($lang_iso), array('EN', 'NL', 'FR', 'DE',)) ? $lang_iso : 'EN';
				$option = new EontechModBpostOrderBoxOptionMessaging(
					'keepMeInformed',
					$lang_iso,
					$receiver->getEmailAddress()
				);
				$at_bpost->addOption($option);
				$delivery_options = $this->getDeliveryBoxOptions('bpost');
				foreach ($delivery_options as $option)
					$at_bpost->addOption($option);

				$box->setNationalBox($at_bpost);
				break;

			case BpostShm::SHIPPING_METHOD_AT_24_7:
				// @24/7
				// Never retour
				$service_point = $this->getServicePointDetails($service_point_id, BpostShm::SHIPPING_METHOD_AT_24_7);
				$bpack247_customer = Tools::jsonDecode($cart->bpack247_customer);

				$parcels_depot_address = new EontechModBpostOrderParcelsDepotAddress(
					$service_point['street'],
					$service_point['nr'],
					'A',
					$service_point['zip'],
					$service_point['city'],
					'BE'
				);

				for ($i = Tools::strlen($service_point['id']); $i < 6; $i++)
					$service_point['id'] = '0'.$service_point['id'];

				$at247 = new EontechModBpostOrderBoxAt247();
				$at247->setParcelsDepotId($service_point['id']);
				$at247->setParcelsDepotName($service_point['office']);
				$at247->setParcelsDepotAddress($parcels_depot_address);
				$at247->setMemberId($bpack247_customer->DeliveryCode);
				$at247->setReceiverName(Tools::substr($receiver->getName(), 0, 40));
				$at247->setReceiverCompany(Tools::substr($receiver->getCompany(), 0, 40));

				$delivery_options = $this->getDeliveryBoxOptions('247');
				foreach ($delivery_options as $option)
					$at247->addOption($option);

				$box->setNationalBox($at247);
				break;
		}
		// new field to insert PS version once per box, instead of once per Order!
		$box->setAdditionalCustomerReference((string)'PrestaShop_'._PS_VERSION_);

		return $box;
	}

	/**
	 * @param string $reference
	 * @return bool
	 */
	public function addLabel($reference = '', $is_retour = false)
	{
		$order_bpost = PsOrderBpost::getByReference($reference);
		return isset($order_bpost) && $order_bpost->addLabel($is_retour);
	}

	/**
	 * @param string $reference bpost order
	 * @return array
	 */
	public function printLabels($reference = '')
	{
		$links = array();

		if (empty($reference))
		{
			$links['Error'][] = 'Unable to print labels';
			return $links;
		}

		try {
			$pdf_manager = new EontechPdfManager($this->module->name, 'pdf', true);
			$pdf_manager->setActiveFolder($reference);

			$order_bpost = PsOrderBpost::getByReference($reference);
			// Shop context is set per row in AdminOrdersBpost controller(1.5+)
			// no need for manual reset here.
			$order_bpost_status = $order_bpost->status;
			$shm = $order_bpost->shm;
			$is_intl = self::isInternational($shm);
			// get all unprinted labels
			$ps_labels = $order_bpost->getNewLabels();
			if (count($ps_labels))
			{
				$ps_order = PsOrderBpost::getPsOrderByReference($reference);

				if (isset($order_bpost_status))
				{
					$bpost_order = $this->bpost->fetchOrder($reference);
					$boxes = $bpost_order->getBoxes();
					$box_1st = $boxes[0];
					$order_bpost_status = $box_1st->getStatus();
					$weight = $is_intl ?
						$box_1st->getInternationalBox()->getParcelWeight() :
						$box_1st->getNationalBox()->getWeight();
				}
				else
				{
					$weight = 0;
					// create a new bpost order
					$order_bpost_status = 'PENDING';
					$bpost_order = new EontechModBpostOrder($reference);
					// $bpost_order->setCostCenter('PrestaShop_'._PS_VERSION_);
					// $bpost_order->setCostCenter('Cost Center');

					// add product lines
					if ($products = $ps_order->getProducts())
						foreach ($products as $product)
						{
							$product_name = self::getBpostring($product['product_name']);
							$line = new EontechModBpostOrderLine($product_name, $product['product_quantity']);
							$bpost_order->addLine($line);
							$weight += $product['product_weight'];
						}

					$weight = (int)$this->getWeightGrams($weight);

				}

				$box = array();
				foreach ($ps_labels as $has_retour => $cur_labels)
				{
					// reset boxes
					$bpost_order->setBoxes(array());

					foreach ($cur_labels as $label_bpost)
					{
						$is_retour = (bool)$label_bpost->is_retour;
						if (!isset($box[$is_retour]))
							$box[$is_retour] = $this->createBox($reference, $shm, $ps_order, $weight, $is_retour);

						$bpost_order->addBox($box[$is_retour]);
					}

					$this->bpost->createOrReplaceOrder($bpost_order);
					// New way
					$bcc = new EontechBarcodeCollection();
					$bpost_labels_returned = $this->createLabelForOrder($reference, (bool)$has_retour);
					// save the labels and record the barcodes
					foreach ($bpost_labels_returned as $bpost_label)
					{
						$bcc->addBarcodes($bpost_label->getBarcodes());
						$pdf_manager->writePdf($bpost_label->getBytes());
					}
					// set local label barcodes
					foreach ($cur_labels as $label_bpost)
					{
						$is_retour = (bool)$label_bpost->is_retour;
						if ($has_retour)
						{
							$barcodes = $bcc->getNextAutoReturn($is_intl);
							$label_bpost->barcode = $barcodes[EontechBarcodeCollection::TYPE_NORMAL];
							$label_bpost->barcode_retour = $barcodes[EontechBarcodeCollection::TYPE_RETURN];
						}
						else
							$label_bpost->barcode = $bcc->getNext($is_retour, $is_intl);

						$label_bpost->status = 'PRINTED';
						$label_bpost->save();
					}

					// update order bpost status
					if ($order_bpost_status !== $order_bpost->status)
					{
						$order_bpost->status = $order_bpost_status;
						$order_bpost->save();
					}
				}
			}

		} catch (Exception $e) {
			$links['Error'][] = $e->getMessage();
			return $links;
		}

		return $pdf_manager->getLinks();
	}

	/**
	 * @param null|string $reference
	 * @return bool
	 */
	public function createLabelForOrder($reference = null, $with_return_labels = false)
	{
		$response = false;

		if (!is_null($reference))
		{
			$reference = Tools::substr($reference, 0, 50);
			$format = Configuration::get('BPOST_LABEL_PDF_FORMAT');

			try {
				$response = $this->bpost->createLabelForOrder($reference, $format, $with_return_labels, true);
			} catch (EontechModException $e) {
				$response = false;
			}

		}

		return $response;
	}

	/**
	 * @param null|string $barcode
	 * @return bool
	 */
	public function createLabelForBox($barcode = null, $with_return_labels = false)
	{
		$response = false;

		if (!is_null($barcode))
		{
			$format = Configuration::get('BPOST_LABEL_PDF_FORMAT');

			try {
				$response = $this->bpost->createLabelForBox($barcode, $format, $with_return_labels, true);
			} catch (EontechModException $e) {
				$response = false;
			}

		}

		return $response;
	}

	/**
	 * @param string|null $reference
	 * @return string
	 */
	public function getOrderStatus($reference = null)
	{
		$status = '-';

		if (!is_null($reference))
		{
			$reference = Tools::substr($reference, 0, 50);

			try {
				if (empty(self::$cache[$reference]) || !$order = self::$cache[$reference])
					$order = $this->bpost->fetchOrder($reference);
				if ($boxes = $order->getBoxes())
					$status = $boxes[0]->getStatus();
			} catch (EontechModException $e) {
				self::logError('getOrderStatus Ref: '.$reference, $e->getMessage(), $e->getCode(), 'Order', isset($order->id) ? $order->id : 0);
				$status = '-';
			}
		}

		return $status;
	}

	/**
	 * getBpack247Member
	 * @param  int 		$rcn 		customer delivery code RC#
	 * @param  string 	$attribs 	required list of attributes (coma delimited)
	 * @return array 	array of requested attributes | Error
	 */
	public function getBpack247Member($rcn, $attribs)
	{
		return $this->bpack247MemberFrom('getMember', array($rcn, true), $attribs);
	}

	/**
	 * getBpack247Member
	 * @param  array 	$customer 	customer's new member details (validated)
	 * @param  string 	$attribs 	required list of attributes (coma delimited)
	 * @return array 	array of requested attributes | Error
	 */
	public function createBpack247Member($customer, $attribs)
	{
		$new_member = new EontechModBpack247Customer();

		if (isset($customer['UserID']) && $customer['UserID'] != '')
			$new_member->setUserID((string)$customer['UserID']);
		if (isset($customer['FirstName']) && $customer['FirstName'] != '')
			$new_member->setFirstName((string)$customer['FirstName']);
		if (isset($customer['LastName']) && $customer['LastName'] != '')
			$new_member->setLastName((string)$customer['LastName']);
		if (isset($customer['Street']) && $customer['Street'] != '')
			$new_member->setStreet((string)$customer['Street']);
		if (isset($customer['Number']) && $customer['Number'] != '')
			$new_member->setNumber((string)$customer['Number']);
		if (isset($customer['CompanyName']) && $customer['CompanyName'] != '')
			$new_member->setCompanyName((string)$customer['CompanyName']);
		if (isset($customer['DateOfBirth']) && $customer['DateOfBirth'] != '')
		{
			$date_time = new \DateTime((string)$customer['DateOfBirth']);
			$new_member->setDateOfBirth($date_time);
		}
		if (isset($customer['DeliveryCode']) && $customer['DeliveryCode'] != '')
			$new_member->setDeliveryCode((string)$customer['DeliveryCode']);
		if (isset($customer['Email']) && $customer['Email'] != '')
			$new_member->setEmail((string)$customer['Email']);
		if (isset($customer['MobilePrefix']) && $customer['MobilePrefix'] != '')
			$new_member->setMobilePrefix(trim((string)$customer['MobilePrefix']));
		if (isset($customer['MobileNumber']) && $customer['MobileNumber'] != '')
			$new_member->setMobileNumber((string)$customer['MobileNumber']);
		if (isset($customer['Postalcode']) && $customer['Postalcode'] != '')
			$new_member->setPostalCode((string)$customer['Postalcode']);
		if (isset($customer['PreferredLanguage']) && $customer['PreferredLanguage'] != '')
			$new_member->setPreferredLanguage((string)$customer['PreferredLanguage']);
		if (isset($customer['ReceivePromotions']) && $customer['ReceivePromotions'] != '')
		{
			$receive_promotions = in_array((string)$customer['ReceivePromotions'], array('true', '1'));
			$new_member->setReceivePromotions($receive_promotions);
		}
		if (isset($customer['actived']) && $customer['actived'] != '')
		{
			$activated = in_array((string)$customer['actived'], array('true', '1'));
			$new_member->setActivated($activated);
		}
		if (isset($customer['Title']) && $customer['Title'] != '')
		{
			$title = (string)$customer['Title'];
			$title = Tools::ucfirst(Tools::strtolower($title));
			if (Tools::substr($title, -1) != '.')
				$title .= '.';

			$new_member->setTitle($title);
		}
		if (isset($customer['Town']) && $customer['Town'] != '')
			$new_member->setTown((string)$customer['Town']);

		return $this->bpack247MemberFrom('createMember', array($new_member), $attribs);
	}

	private function bpack247MemberFrom($method, $args, $with_attribs)
	{
		$member = array();
		$bpack247 = new EontechModBpack247(
			self::BPACK247_ID,
			self::BPACK247_PASS
		);

		try {
			if (!$xml = call_user_func_array( array($bpack247, $method), $args ))
				$member['Error'] = 'Server or Developer Error !';
			elseif (!isset($xml->DeliveryCode))
				$member['Error'] = $method.': Invalid RC code';

		} catch (EontechModException $e) {
			$member['Error'] = $e->getMessage();
		}

		if (isset($member['Error']))
			return $member;

		$attribs = explode(',', $with_attribs);
		foreach ($attribs as $attrib)
		{
			$attrib = trim($attrib);
			$node = self::xmlSearch($attrib, $xml);
			if (isset($node))
				$member[$attrib] = (string)$node;
		}

		return $member;
	}

	private static function xmlSearch($what, $nodes)
	{
		foreach ($nodes as $key => $value)
			if ($what == $key)
				return $value;
			elseif ($value->count())
				return self::xmlSearch($what, $value->children());

		return null;
	}

	private static function logError($func, $msg, $err_code, $obj, $obj_id)
	{
		$msg_format = 'BpostSHM::Service: '.$func.' - '.$msg;
		Logger::addLog(
			$msg_format,
			3,
			$err_code,
			$obj,
			(int)$obj_id,
			true
		);
	}

	private function getInternationalSlug($short = false)
	{
		$setting = (int)Configuration::get('BPOST_INTERNATIONAL_DELIVERY');
		$slug = self::$_slugs_international[$setting];
		return $short ? str_replace('bpack ', '', $slug) : $slug;
	}

	/**
	 * [getDeliveryOptionsList description]
	 * @param  string $deliveryMethod (home, bpost, 247 or intl)
	 * @return string                 empty or option keys seperated by |
	 */
	private function getDeliveryOptionsList($delivery_method, $prepend = '')
	{
		$list = '';
		if ($options_list = Configuration::get('BPOST_DELIVERY_OPTIONS_LIST'))
		{
			$options_list = Tools::jsonDecode($options_list, true);
			if (isset($options_list[$delivery_method]))
				$list = $options_list[$delivery_method];

			$list = empty($list) ? '' : $prepend.$list;
		}

		return $list;
	}

	private function getDeliveryBoxOptions($delivery_method)
	{
		$options = array();
		$options_list = $this->getDeliveryOptionsList($delivery_method);
		if (!empty($options_list))
		{
			$option_keys = explode('|', $options_list);
			foreach ($option_keys as $key)
				switch ($key)
				{
					case '300': // Signature
						$options[] = new EontechModBpostOrderBoxOptionSigned();
						break;

					case '330': // 2nd Presentation
						$options[] = new EontechModBpostOrderBoxOptionAutomaticSecondPresentation();
						break;

					case '540':
					case '350': // Insurance
						$options[] = new EontechModBpostOrderBoxOptionInsured('basicInsurance');
						break;

					case '470': // Saturday delivery (Not yet implemented)
						// $options[] = new EontechModBpostOrderBoxOption_XXX();
						break;

					default:
						throw new Exception('Not a valid delivery option');
						break;
				}
		}

		return $options;
	}

	public function getDeliveryOptions($selection)
	{
		return $this->module->getDeliveryOptions($selection);
	}

	/**
	 * [getModuleLink description]
	 * @param  string $module     name
	 * @param  string $controller name
	 * @param  array $params     request params
	 * @return string             Module front controller link
	 */
	public function getModuleLink($module, $controller, $params)
	{
		if (self::isPrestashop15plus())
			return $this->context->link->getModuleLink($module, $controller, $params);
		else
			return _MODULE_DIR_.$module.'/controllers/front/'.$controller.'.php?'.http_build_query($params);
	}

	public function getControllerLink($module, $controller, $params)
	{
		$params['ps14'] = !self::isPrestashop15plus();
		if (self::isPrestashop15plus())
			$params['shop_id'] = $this->context->shop->id;
		return _MODULE_DIR_.$module.'/controllers/front/'.$controller.'.php?'.http_build_query($params);
	}

	/**
	 * get full list bpost enabled countries
	 * @return assoc array
	 */
	public function getProductCountries()
	{
		$product_countries_list = 'BE';

		try {
			if ($product_countries = $this->bpost->getProductCountries())
				$product_countries_list = implode('|', $product_countries);

		} catch (Exception $e) {
			return array('Error' => $e->getMessage());

		}

		return $this->explodeCountryList($product_countries_list);
	}

	/**
	 * [explodeCountryList]
	 * @param  string $iso_list delimited list of iso country codes
	 * @param  string $glue     delimiter
	 * @return array            assoc array of ps_countries [iso => name]
	 */
	public function explodeCountryList($iso_list, $glue = '|')
	{
		$iso_list = str_replace($glue, "','", pSQL($iso_list));
		$query = '
SELECT
	c.id_country as id, c.iso_code as iso, cl.name
FROM
	`'._DB_PREFIX_.'country` c, `'._DB_PREFIX_.'country_lang` cl
WHERE
	cl.id_lang = '.(int)$this->context->language->id.'
AND
	c.id_country = cl.id_country
AND
	c.iso_code in (\''.$iso_list.'\')
ORDER BY
	name
		';

		$countries = array();
		try {
			$db = Db::getInstance(_PS_USE_SQL_SLAVE_);
			if ($results = $db->ExecuteS($query))
				foreach ($results as $row)
					$countries[$row['iso']] = $row['name'];

		} catch (Exception $e) {
			$countries = array();
		}

		return array_filter($countries);
	}

}