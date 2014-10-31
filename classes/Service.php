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

/*
	private static $_delivery_options_all = array(
		300 => 'Signature',
		330 => '2nd Presentation',
		350 => 'Insurance',
		470 => 'Saturday Delivery',
		540 => 'Insurance basic',
		);
*/

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
	public $bpost;
	private $context;
	private $geo6;

	/**
	 * @param Context $context
	 */
	public function __construct(Context $context)
	{
		require_once(_PS_MODULE_DIR_.'bpostshm/classes/Autoloader.php');
		if (Service::isPrestashopFresherThan14())
			spl_autoload_register(array(Autoloader::getInstance(), 'load'));
		else
			spl_autoload_register(array(Autoloader::getInstance(), 'loadPS14'));

		$this->context = $context;
		$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);

		$this->bpost = new TijsVerkoyenBpostBpost(
			Configuration::get('BPOST_ACCOUNT_ID_'.$context_shop_id),
			Configuration::get('BPOST_ACCOUNT_PASSPHRASE_'.$context_shop_id)
		);
		$this->geo6 = new TijsVerkoyenBpostGeo6(
			self::GEO6_PARTNER,
			self::GEO6_APP_ID
		);
		$this->module = new BpostShm();

		$this->delivery_methods_list = array(
			(int)Configuration::get('BPOST_SHIP_METHOD_'.BpostShm::SHIPPING_METHOD_AT_HOME.'_ID_CARRIER_'
					.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id))
				=> $this->module->shipping_methods[BpostShm::SHIPPING_METHOD_AT_HOME]['slug'],
			(int)Configuration::get('BPOST_SHIP_METHOD_'.BpostShm::SHIPPING_METHOD_AT_SHOP.'_ID_CARRIER_'
					.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id))
				=> $this->module->shipping_methods[BpostShm::SHIPPING_METHOD_AT_SHOP]['slug'],
			(int)Configuration::get('BPOST_SHIP_METHOD_'.BpostShm::SHIPPING_METHOD_AT_24_7.'_ID_CARRIER_'
					.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id))
				=> $this->module->shipping_methods[BpostShm::SHIPPING_METHOD_AT_24_7]['slug'],
		);

		/*
		 * Retrieve tracking status from a barcode
		 *
		 * Tools::d($this->doCall(
			'https://api.bpost.be/services/trackedmail/item/323210744759901842800050/trackingInfo'
		));*/
	}

	public static function isPrestashopFresherThan14()
	{
		return version_compare(_PS_VERSION_, '1.5', '>=');
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

	public static function isPrestashop16()
	{
		return version_compare(_PS_VERSION_, '1.6', '>');
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
				$this->context->language->id, $type))
			{
				foreach ($response as $row)
				{
					$service_points['coords'][] = array(
						$row['poi']->getLatitude(),
						$row['poi']->getLongitude(),
					);
					$service_points['list'][] = array(
						'city' 			=> $row['poi']->getCity(),
						'id' 			=> $row['poi']->getId(),
						'office' 		=> $row['poi']->getOffice(),
						'nr' 			=> $row['poi']->getNr(),
						'street' 		=> $row['poi']->getstreet(),
						'zip' 			=> $row['poi']->getZip(),
					);
				}
			}
		} catch (TijsVerkoyenBpostException $e) {
			$service_points = array();
		}

		return $service_points;
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
			if ($response = $this->geo6->getServicePointDetails($service_point_id, $this->context->language->id, $type))
				if ($service_point_days = $response->getHours())
					foreach ($service_point_days as $day)
						$service_point_hours[$day->getDay()] = array(
							'am_open' => $day->getAmOpen(),
							'am_close' => $day->getAmClose(),
							'pm_open' => $day->getPmOpen(),
							'pm_close' => $day->getPmClose(),
						);
		} catch (TijsVerkoyenBpostException $e) {
			$service_point_hours = array();
		}

		return $service_point_hours;
	}

	/**
	 * @param array $params
	 * @param int $type
	 * @param bool $is_retour
	 * @param string|null $reference
	 * @return TijsVerkoyenBpostBpostOrder
	 */
	public function makeOrder($id_order = 0, $type = 3, $is_retour = false, $reference = null)
	{
		$response = true;

		if (empty($id_order) || !is_numeric($id_order))
			return !$response;

		if ($is_retour)
			$type = 1;

		$weight = 0;
		$ps_order = new Order((int)$id_order);

		if ($this->isPrestashopFresherThan14())
			$reference = Configuration::get('BPOST_ACCOUNT_ID_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id)).'_'
				.Tools::substr($ps_order->reference, 0, 53);
		elseif (is_null($reference))
			$reference = Configuration::get('BPOST_ACCOUNT_ID_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id)).'_'
				.Tools::substr($ps_order->id, 0, 42).'_'.time();

		$order = new TijsVerkoyenBpostBpostOrder($reference);
		$order->setCostCenter('PrestaShop_'._PS_VERSION_);

		// add product lines
		if ($products = $ps_order->getProducts())
			foreach ($products as $product)
			{
				$line = new TijsVerkoyenBpostBpostOrderLine($product['product_name'], $product['product_quantity']);
				$order->addLine($line);
				$weight += $product['product_weight'];
			}
		if (empty($weight))
			$weight = 1;
		$weight *= 1000;

		$service_point_id = null;
		if (in_array((int)$type, array(BpostShm::SHIPPING_METHOD_AT_SHOP, BpostShm::SHIPPING_METHOD_AT_24_7)))
		{
			$cart = new Cart($ps_order->id_cart);
			$service_point_id = (int)$cart->service_point_id;
		}

		// add box
		if ($is_retour)
		{
			$shippers = $this->getReceiverAndSender($ps_order, true);

			if (empty(self::$cache[$reference]) || !$base_order = self::$cache[$reference])
				$base_order = $this->bpost->fetchOrder($reference);
			foreach ($base_order->getBoxes() as $box)
				if ($national_box = $box->getNationalBox())
					if (!in_array($national_box->getProduct(), array('bpack Easy Retour',/* 'bpack World Easy Return',*/)))
					{
						$response = $response && $this->addBox(
							$order,
							(int)$type,
							$shippers['sender'],
							$shippers['receiver'],
							$weight,
							$service_point_id,
							$box
						);
					}
		}
		else
		{
			$shippers = $this->getReceiverAndSender($ps_order);
			$response = $response && $this->addBox($order, (int)$type, $shippers['sender'], $shippers['receiver'], $weight, $service_point_id);
		}

		try {
			$response = $response && $this->bpost->createOrReplaceOrder($order);
			//$response &= $this->updateOrderStatus($reference);
			//$response &= $this->bpost->modifyOrderStatus($order->getReference(), 'OPEN');

			if ($is_retour)
			{
				if (empty(self::$cache[$reference]) || !$base_order = self::$cache[$reference])
					$base_order = $this->bpost->fetchOrder($reference);
				foreach ($base_order->getBoxes() as $box)
					if ($national_box = $box->getNationalBox())
						if (!in_array($national_box->getProduct(), array('bpack Easy Retour',/* 'bpack World Easy Return',*/)))
							$response = $response && $this->createPSLabel($reference);
			}
			else
				$response = $response && $this->createPSLabel($reference);

		//} catch (TijsVerkoyenBpostException $e) {
		} catch (Exception $e) {
			self::logError('makeOrder Ref: '.$reference, $e->getMessage(), $e->getCode(), 'Order', $id_order);
			$response = false;
		}

		return $response;
	}

	/**
	 * @param Order $ps_order
	 * @param bool $is_retour
	 * @return array
	 */
	public function getReceiverAndSender($ps_order, $is_retour = false)
	{
		$customer = new Customer((int)$ps_order->id_customer);
		$delivery_address = new Address($ps_order->id_address_delivery, $this->context->language->id);
		$invoice_address = new Address($ps_order->id_address_invoice, $this->context->language->id);

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
				'name'		=> Configuration::get('PS_SHOP_NAME'),
				'phone'		=> Configuration::get('PS_SHOP_PHONE'),
				'postcode' 	=> Configuration::get('PS_SHOP_CODE'),
			),
		);

		if (!empty($delivery_address->company))
			$shippers['client']['company'] = $delivery_address->company;

		$sender = $shippers['shop'];
		$receiver = $shippers['client'];
		if ($is_retour)
		{
			/*
			// Try this
			// If service_point_delivered
			$sp_delivered = false;
			if ($sp_delivered)
			{
				$shippers['client']['address1'] = $invoice_address->address1;
				$shippers['client']['address2'] = $invoice_address->address2;
				$shippers['client']['city'] = $invoice_address->city;
				$shippers['client']['id_country'] = $invoice_address->id_country;
				$shippers['client']['postcode'] = $invoice_address->postcode;
			}
			//*/
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

		$address = new TijsVerkoyenBpostBpostOrderAddress();
		$address->setNumber(Tools::substr($nr, 0, 8));
		$address->setStreetName(Tools::substr($street.(!empty($sender['address2']) && !is_numeric($sender['address2'])
			? ' '.$sender['address2'] : ''), 0, 40));
		$address->setPostalCode(Tools::substr((int)$sender['postcode'], 0, 32));
		$address->setLocality(Tools::substr($sender['city'], 0, 40));
		$address->setCountryCode(Tools::strtoupper(Country::getIsoById($sender['id_country'])));

		$bpost_sender = new TijsVerkoyenBpostBpostOrderSender();
		$bpost_sender->setAddress($address);
		$bpost_sender->setName(Tools::substr($sender['name'], 0, 40));
		if (!empty($sender['company']))
			$bpost_sender->setCompany(Tools::substr($sender['company'], 0, 40));
		$bpost_sender->setPhoneNumber(Tools::substr($sender['phone'], 0, 20));
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

		$address = new TijsVerkoyenBpostBpostOrderAddress();
		$address->setNumber(Tools::substr($nr, 0, 8));
		$address->setStreetName(Tools::substr($street.(!empty($receiver['address2']) && !is_numeric($receiver['address2'])
			? ' '.$receiver['address2'] : ''), 0, 40));
		$address->setPostalCode(Tools::substr((int)$receiver['postcode'], 0, 32));
		$address->setLocality(Tools::substr($receiver['city'], 0, 40));
		$address->setCountryCode(Tools::strtoupper(Country::getIsoById($receiver['id_country'])));

		$bpost_receiver = new TijsVerkoyenBpostBpostOrderReceiver();
		$bpost_receiver->setAddress($address);
		$bpost_receiver->setName(Tools::substr($receiver['name'], 0, 40));
		if (!empty($receiver['company']))
			$bpost_receiver->setCompany(Tools::substr($receiver['company'], 0, 40));
		$bpost_receiver->setPhoneNumber(Tools::substr($receiver['phone'], 0, 20));
		$bpost_receiver->setEmailAddress(Tools::substr($receiver['email'], 0, 50));

		return array(
			'receiver' => $bpost_receiver,
			'sender' => $bpost_sender,
		);
	}

	/**
	 * @param TijsVerkoyenBpostBpostOrder $order
	 * @param int $type
	 * @param TijsVerkoyenBpostBpostOrderSender $sender
	 * @param TijsVerkoyenBpostBpostOrderReceiver $receiver
	 * @param int $weight @todo Find a cleaner way to manage weight
	 * @param null $service_point_id
	 * @param TijsVerkoyenBpostBpostOrderBox $box
	 * @return bool
	 */
	public function addBox(TijsVerkoyenBpostBpostOrder $order = null, $type = 0, TijsVerkoyenBpostBpostOrderSender $sender,
		TijsVerkoyenBpostBpostOrderReceiver $receiver, $weight = 1000, $service_point_id = null, $box = null)
	{
		$response = true;

		if (empty($order) || empty($type)
				|| ((int)$type == (int)BpostShm::SHIPPING_METHOD_AT_SHOP && is_null($service_point_id)))
			return !$response;

		$is_retour = false;
		if (!is_null($box))
			$is_retour = true;
		else
		{
			$box = new TijsVerkoyenBpostBpostOrderBox();
			$box->setStatus('OPEN');
		}
		$box->setSender($sender);

		switch ((int)$type)
		{
			case (int)BpostShm::SHIPPING_METHOD_AT_HOME:
				if (empty($weight))
					$weight = 1000;

				$is_international = false;
				$id_zone_be = Configuration::get('BPOST_ID_COUNTRY_BELGIUM_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id));
				$receiver_id_country = Country::getByIso($receiver->getAddress()->getCountryCode());

				if ($id_zone_be != Country::getIdZone((int)$receiver_id_country))
					$is_international = true;

				if ($this->isPrestashopFresherThan14())
					$ps_order = Order::getByReference(Tools::substr($order->getReference(), 7))->getFirst();
				else
					$ps_order = new Order((int)Tools::substr($order->getReference(), 7));

				if ($is_international)
				{
					// @International
					$customs_info = new TijsVerkoyenBpostBpostOrderBoxCustomsinfoCustomsInfo();
					$customs_info->setParcelValue((float)$ps_order->total_paid * 100);
					$customs_info->setContentDescription('ORDER '.Configuration::get('PS_SHOP_NAME'));
					$customs_info->setShipmentType('OTHER');
					$customs_info->setParcelReturnInstructions('RTS');
					$customs_info->setPrivateAddress(false);

					$international = new TijsVerkoyenBpostBpostOrderBoxInternational();
					/*if ($is_retour)
						$international->setProduct('bpack World Easy Return');
					else*/
						//$international->setProduct('bpack World Express Pro');
					// $international_options = array(
					// 	'bpack World Express Pro',
					// 	'bpack World Business',
					// 	);
					// $international_delivery = Configuration::get('BPOST_INTERNATIONAL_DELIVERY_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id));
					//$international->setProduct($international_options[$international_delivery]);
					$international->setProduct($this->getInternationalSlug());
					$international->setReceiver($receiver);
					$international->setParcelWeight($weight);
					$international->setCustomsInfo($customs_info);

					$delivery_options = $this->getDeliveryBoxOptions('intl');
					foreach ($delivery_options as $option)
						$international->addOption($option);

					$box->setInternationalBox($international);
				}
				else
				{
					// @Home
					$at_home = new TijsVerkoyenBpostBpostOrderBoxAtHome();
					$at_home->setWeight($weight);
					$at_home->setReceiver($receiver);
					if ($is_retour)
						$at_home->setProduct('bpack Easy Retour');
					else
					{
						$at_home->setProduct('bpack 24h Pro');
						/*
						$option = new TijsVerkoyenBpostBpostOrderBoxOptionMessaging(
							'infoDistributed',
							$this->context->language->iso_code,
							$sender->getEmailAddress()
						);
						$at_home->addOption($option);
						*/
						$delivery_options = $this->getDeliveryBoxOptions('home');
						foreach ($delivery_options as $option)
							$at_home->addOption($option);
					}

					$box->setNationalBox($at_home);
				}
				break;

			case (int)BpostShm::SHIPPING_METHOD_AT_SHOP:
				// @Bpost
				$service_point = $this->getServicePointDetails($service_point_id, BpostShm::SHIPPING_METHOD_AT_SHOP);
				$pugo_address = new TijsVerkoyenBpostBpostOrderPugoAddress(
					$service_point['street'],
					$service_point['nr'],
					null,
					$service_point['zip'],
					$service_point['city'],
					'BE'
				);

				$at_bpost = new TijsVerkoyenBpostBpostOrderBoxAtBpost();
				$at_bpost->setPugoId($service_point_id);
				$at_bpost->setPugoName(Tools::substr($service_point['office'], 0, 40));
				$at_bpost->setPugoAddress($pugo_address);
				$at_bpost->setReceiverName(Tools::substr($receiver->getName(), 0, 40));
				$at_bpost->setReceiverCompany(Tools::substr($receiver->getCompany(), 0, 40));

				/*
				$option = new TijsVerkoyenBpostBpostOrderBoxOptionMessaging(
					'keepMeInformed',
					$this->context->language->iso_code,
					$sender->getEmailAddress()
				);
				*/
				// language must default to EN if not in allowed values
				$lang_iso = $this->context->language->iso_code;
				$lang_iso = in_array(strtoupper($lang_iso), array('EN', 'NL', 'FR', 'DE',)) ? $lang_iso : 'EN';
				$option = new TijsVerkoyenBpostBpostOrderBoxOptionMessaging(
					'keepMeInformed',
					$lang_iso,
					$receiver->getEmailAddress()
					// $is_retour ? $sender->getEmailAddress() : $receiver->getEmailAddress()
				);
				$at_bpost->addOption($option);
				$delivery_options = $this->getDeliveryBoxOptions('bpost');
				foreach ($delivery_options as $option)
					$at_bpost->addOption($option);

				$box->setNationalBox($at_bpost);
				break;

			case (int)BpostShm::SHIPPING_METHOD_AT_24_7:
				// @24/7
				if ($this->isPrestashopFresherThan14())
					$ps_order = Order::getByReference(Tools::substr($order->getReference(), 7))->getFirst();
				else
					$ps_order = new Order((int)Tools::substr($order->getReference(), 7));

				$service_point = $this->getServicePointDetails($service_point_id, BpostShm::SHIPPING_METHOD_AT_24_7);
				$cart = new Cart((int)$ps_order->id_cart);
				$bpack247_customer = Tools::jsonDecode($cart->bpack247_customer);

				$parcels_depot_address = new TijsVerkoyenBpostBpostOrderParcelsDepotAddress(
					$service_point['street'],
					$service_point['nr'],
					'A',
					$service_point['zip'],
					$service_point['city'],
					'BE'
				);

				for ($i = Tools::strlen($service_point['id']); $i < 6; $i++)
					$service_point['id'] = '0'.$service_point['id'];

				$at247 = new TijsVerkoyenBpostBpostOrderBoxAt247();
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

		$order->addBox($box);

		return $response;
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
			if ($poi = $this->geo6->getServicePointDetails($service_point_id, $this->context->language->id, $type))
			{
				$service_point_details['id'] 		= $poi->getId();
				$service_point_details['office'] 	= $poi->getOffice();
				$service_point_details['street'] 	= $poi->getStreet();
				$service_point_details['nr'] 		= $poi->getNr();
				$service_point_details['zip'] 		= $poi->getZip();
				$service_point_details['city'] 		= $poi->getCity();
			}
		} catch (TijsVerkoyenBpostException $e) {
			$service_point_details = array();
		}

		return $service_point_details;
	}

	/**
	 * @param string $reference
	 * @param string $status
	 * @return bool
	 */
	public function createPSLabel($reference = '', $status = 'PENDING')
	{
		$response = true;

		if (empty($reference) || empty($status))
			return !$response;

		$reference = Tools::substr($reference, 0, 50);
		$recipient = $this->getOrderRecipient($reference);
		if ($this->isPrestashopFresherThan14())
			$ps_order = Order::getByReference(Tools::substr($reference, 7))->getFirst();
		else
			$ps_order = new Order((int)Tools::substr($reference, 7));
		$delivery_method = $this->getOrderShippingMethod((int)$ps_order->id_carrier, true);
/*
		if ($address = Address::getCountryAndState((int)$ps_order->id_address_delivery))
		{
			$country = new Country((int)$address['id_country']);

			if ($delivery_method == $this->module->shipping_methods[BpostShm::SHIPPING_METHOD_AT_HOME]['slug'] && 'BE' != $country->iso_code)
				$delivery_method = $this->getInternationalSlug(true);
				// $delivery_method = '@international';
		}
*/
		switch ($delivery_method)
		{
			case $this->module->shipping_methods[BpostShm::SHIPPING_METHOD_AT_HOME]['slug']:
				if ($address = Address::getCountryAndState((int)$ps_order->id_address_delivery))
				{
					$country = new Country((int)$address['id_country']);

					if ('BE' != $country->iso_code)
					{
						// $delivery_method = '@international';
						$options_list = $this->getDeliveryOptionsList('intl', ':');
						$delivery_method = $this->getInternationalSlug(true).$options_list;
					}
					else
						// Belgian address
						$delivery_method .= $this->getDeliveryOptionsList('home', ':');
				}
				break;

			case $this->module->shipping_methods[BpostShm::SHIPPING_METHOD_AT_SHOP]['slug']:
				$delivery_method .= $this->getDeliveryOptionsList('bpost', ':');
				break;

			case $this->module->shipping_methods[BpostShm::SHIPPING_METHOD_AT_24_7]['slug']:
				// $delivery_method .= $this->getDeliveryOptionsList('247', ':');
				$delivery_method = 'Parcel locker'.$this->getDeliveryOptionsList('247', ':');
				break;

		}

		$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);
		$query = '
INSERT INTO
	'._DB_PREFIX_.'order_label
(
	`reference`,
	`id_shop`,
	`status`,
	`delivery_method`,
	`recipient`,
	`date_add`
)
VALUES(
	"'.pSQL($reference).'",
	'.(int)$context_shop_id.',
	"'.pSQL($status).'",
	"'.pSQL($delivery_method).'",
	"'.pSQL($recipient).'",
	NOW()
)';
		$response &= Db::getInstance()->execute($query);

		return $response;
	}

	/**
	 * @param string|null $reference
	 * @return string
	 */
	public function getOrderRecipient($reference = null)
	{
		$recipient = '-';

		if (!is_null($reference))
		{
			$reference = Tools::substr($reference, 0, 50);

			try {
				if (empty(self::$cache[$reference]) || !$order = self::$cache[$reference])
					$order = $this->bpost->fetchOrder($reference);

				if ($order_boxes = $order->getBoxes())
					foreach ($order_boxes as $box)
						if ($national_box = $box->getNationalBox())
						{
							if (in_array($national_box->getProduct(), array('bpack Easy Retour',)))
								continue;

							// @home
							if (method_exists($national_box, 'getReceiver'))
							{
								$receiver = $national_box->getReceiver();
								$address = $receiver->getAddress();
								$recipient = $receiver->getName().' '.$address->getStreetName().' '.$address->getNumber().' '
									.$address->getPostalCode().' '.$address->getLocality();
								break;
							}
							// @bpost
							elseif (method_exists($national_box, 'getPugoAddress'))
							{
								$address = $national_box->getPugoAddress();
								$recipient = $national_box->getReceiverName().' '.$national_box->getPugoName().' '.$address->getPostalCode().' '
									.$address->getLocality();
								break;
							}
							// @24/7
							elseif (method_exists($national_box, 'getParcelsDepotaddress'))
							{
								$address = $national_box->getParcelsDepotaddress();
								$recipient = $national_box->getReceiverName().' '.$national_box->getParcelsDepotname().' '
									.$address->getPostalCode().' '.$address->getLocality();
								break;
							}
						}
						elseif ($international_box = $box->getInternationalBox())
						{
							if (in_array($international_box->getProduct(), array('bpack World Easy Return',)))
								continue;

							$receiver = $international_box->getReceiver();
							$address = $receiver->getAddress();
							$recipient = $receiver->getName().' '.$address->getStreetName().' '.$address->getNumber().' '
								.$address->getPostalCode().' '.$address->getLocality();
							break;
						}
			//} catch (TijsVerkoyenBpostException $e) {
			} catch (Exception $e) {
				self::logError('getOrderRecipient Ref: '.$reference, $e->getMessage(), $e->getCode(), 'Order', isset($order->id) ? $order->id : 0);
				$recipient = '-';
			}
		}

		return $recipient;
	}

	/**
	 * @param int $id_carrier
	 * @param bool $slug
	 * @return mixed|string
	 */
	public function getOrderShippingMethod($id_carrier = 0, $slug = true)
	{
		$shipping_method = '';

		if (Service::isPrestashopFresherThan14())
		{
			$id_reference = Db::getInstance()->getValue('
SELECT
	MAX(cc.`id_carrier`)
FROM
	`'._DB_PREFIX_.'carrier` c
LEFT JOIN
	`'._DB_PREFIX_.'carrier` cc
ON
	cc.`id_reference` = c.`id_reference`
WHERE
	c.`id_carrier` = '.(int)$id_carrier);
		}
		else
		{
			$id_reference = Db::getInstance()->getValue('
SELECT
	c.`id_carrier`
FROM
	`'._DB_PREFIX_.'carrier` c
WHERE
	c.`id_carrier` = '.(int)$id_carrier);
		}

		if (!empty($id_reference))
		{
			$shipping_method = $this->delivery_methods_list[(int)$id_reference];
			if (!$slug)
				$shipping_method = array_search($this->delivery_methods_list[(int)$id_reference], $this->delivery_methods_list);
		}

		return $shipping_method;
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
			} catch (TijsVerkoyenBpostException $e) {
				self::logError('getOrderStatus Ref: '.$reference, $e->getMessage(), $e->getCode(), 'Order', isset($order->id) ? $order->id : 0);
				$status = '-';
			}
		}

		return $status;
	}

	/**
	 * @param null|string $reference
	 * @param string $status
	 * @return bool
	 */
	public function updateOrderStatus($reference = null, $status = 'PENDING')
	{
		$response = false;

		if (!is_null($reference))
		{
			$reference = Tools::substr($reference, 0, 50);

			try {
				$response = $this->bpost->modifyOrderStatus($reference, $status);
			} catch (TijsVerkoyenBpostException $e) {
				self::logError('updateOrderStatus Ref: '.$reference.', Status '.$status , $e->getMessage(), $e->getCode(), 'Order', 0);
				$response = false;
			}
		}

		return $response;
	}

	/**
	 * @param null|string $reference
	 * @return bool
	 */
	public function createLabelForOrder($reference = null, $format = 'A4', $with_return_labels = false)
	{
		$response = false;

		if (!is_null($reference))
		{
			$reference = Tools::substr($reference, 0, 50);

			try {
				$response = $this->bpost->createLabelForOrder($reference, $format, $with_return_labels, true);
			} catch (TijsVerkoyenBpostException $e) {
				$response = false;
			}
		}

		return $response;
	}

	/**
	 * @param string $reference
	 * @param string $status
	 * @return bool
	 */
	public function updatePSLabelStatus($reference = '', $status = 'PENDING')
	{
		if (empty($reference) || empty($status))
			return false;

		$reference = Tools::substr($reference, 0, 50);

		$response = Db::getInstance()->execute('
UPDATE
	'._DB_PREFIX_.'order_label
SET
	`status` = "'.pSQL($status).'"
WHERE
	`reference` = "'.pSQL($reference).'"');

		return $response;
	}

	/**
	 * @param string $reference
	 * @param string $barcode
	 * @return bool
	 */
	public function updatePSLabelBarcode($reference = '', $barcode = '')
	{
		if (empty($reference) || empty($barcode))
			return false;

		$reference = Tools::substr($reference, 0, 50);

		return Db::getInstance()->execute('
UPDATE
	'._DB_PREFIX_.'order_label
SET
	`barcode` = "'.pSQL($barcode).'"
WHERE
	`reference` = "'.pSQL($reference).'"
AND
	`barcode` IS NULL'
/*LIMIT
	1'*/);
	}

	/**
	 * @param string $reference
	 * @return array
	 */
	public function getPSLabels($reference = '')
	{
		$order = array(
			'reference' => $reference,
			'labels'	=> array(),
		);

		if (empty($reference))
			return $order;

		$reference = Tools::substr($reference, 0, 50);
		$query = '
SELECT
	ol.`barcode`, ol.`status`
FROM
	'._DB_PREFIX_.'order_label ol
WHERE
	ol.`reference` = "'.pSQL($reference).'"';

		if ($result = Db::getInstance()->executeS($query))
			foreach ($result as $row)
				$order['labels'][] = $row;

		return $order;
	}

	/**
	 * @param string $reference
	 * @param bool $retour_only
	 * @return bool
	 */
	public function addLabel($reference = '', $retour_only = false)
	{
		$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);
		$response = true;
		$order = $this->bpost->fetchOrder($reference);

		if ($this->isPrestashopFresherThan14())
			$ps_order = Order::getByReference(Tools::substr($reference, 7))->getFirst();
		else
			$ps_order = new Order((int)Tools::substr($reference, 7));

		$cart = new Cart((int)$ps_order->id_cart);
		$id_carrier = $this->getOrderShippingMethod($ps_order->id_carrier, false);

		$boxes = $order->getBoxes();
		$box = $boxes[0];
		// Remove existing boxes so that they won't get duplicated
		$order->setBoxes(array());

		switch ($id_carrier)
		{
			case (int)Configuration::get('BPOST_SHIP_METHOD_'.BpostShm::SHIPPING_METHOD_AT_HOME.'_ID_CARRIER_'.$this->context->shop->id):
			default:
				$type = BpostShm::SHIPPING_METHOD_AT_HOME;
				break;
			case (int)Configuration::get('BPOST_SHIP_METHOD_'.BpostShm::SHIPPING_METHOD_AT_SHOP.'_ID_CARRIER_'.$this->context->shop->id):
				$type = BpostShm::SHIPPING_METHOD_AT_SHOP;
				break;
			case (int)Configuration::get('BPOST_SHIP_METHOD_'.BpostShm::SHIPPING_METHOD_AT_24_7.'_ID_CARRIER_'.$this->context->shop->id):
				$type = BpostShm::SHIPPING_METHOD_AT_24_7;
				break;
		}

		if ((bool)Configuration::get('BPOST_LABEL_RETOUR_LABEL_'.$context_shop_id))
		{
			$shippers = $this->getReceiverAndSender($ps_order, true);

			$response = $response && $this->addBox($order, (int)$type, $shippers['sender'], $shippers['receiver'], null, $cart->service_point_id, $box);
			$response = $response && $this->createPSLabel($order->getReference());
		}

		if (!$retour_only)
		{
			$shippers = $this->getReceiverAndSender($ps_order);
			$response = $response && $this->addBox($order, (int)$type, $shippers['sender'], $shippers['receiver'], null, $cart->service_point_id);
			$response = $response && $this->createPSLabel($order->getReference());
		}

		return $response && $this->bpost->createOrReplaceOrder($order);
	}

	/**
	 * Make the call
	 *
	 * @param  string $url       The URL to call.
	 * @param  string $body      The data to pass.
	 * @param  array  $headers   The headers to pass.
	 * @param  string $method    The HTTP-method to use.
	 * @param  bool   $expect_xml Do we expect XML?
	 * @return mixed
	 */
	private function doCall($url, $body = null, $headers = array(), $method = 'GET', $expect_xml = true)
	{
		// build Authorization header
		$headers[] = 'Authorization: Basic '.$this->getAuthorizationHeader();

		// set options
		$options = array();
		$options[CURLOPT_URL] = $url;
		if ($this->bpost->getPort() != 0)
			$options[CURLOPT_PORT] = $this->bpost->getPort();
		$options[CURLOPT_USERAGENT] = $this->bpost->getUserAgent();
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_TIMEOUT] = (int)$this->bpost->getTimeOut();
		$options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
		$options[CURLOPT_HTTPHEADER] = $headers;
		$options[CURLOPT_SSL_VERIFYPEER] = false;

		if ($method == 'POST')
		{
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $body;
		}

		// init
		$curl = curl_init();

		// set options
		curl_setopt_array($curl, $options);

		// execute
		$response = curl_exec($curl);
		$headers = curl_getinfo($curl);

		// fetch errors
		$error_number = curl_errno($curl);
		$error_message = curl_error($curl);

		// error?
		if ($error_number != '')
			throw new Exception($error_message, $error_number);

		// valid HTTP-code
		if (!in_array($headers['http_code'], array(0, 200, 201)))
		{
			// convert into XML
			$xml = simplexml_load_string($response);

			// validate
			if ($xml !== false && (Tools::substr($xml->getName(), 0, 7) == 'invalid'))
			{
				// message
				$message = (string)$xml->error;
				$code = isset($xml->code) ? (int)$xml->code : null;

				// throw exception
				throw new Exception($message, $code);
			}

			if ((isset($headers['content_type']) && substr_count($headers['content_type'], 'text/plain') > 0) ||
				($headers['http_code'] == '404'))
				$message = $response;
			else
				$message = 'Invalid response.';

			throw new Exception($message, $headers['http_code']);
		}

		// if we don't expect XML we can return the content here
		if (!$expect_xml)
			return $response;

		// convert into XML
		$xml = simplexml_load_string($response);

		// return the response
		return $xml;
	}

	/**
	 * Generate the secret string for the Authorization header
	 *
	 * @return string
	 */
	private function getAuthorizationHeader()
	{
		return base64_encode($this->bpost->getAccountId().':'.$this->bpost->getPassPhrase());
	}

/* ************
 * SRG section
 * ************
 */

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
		$new_member = new TijsVerkoyenBpostBpack247Customer();

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
		$bpack247 = new TijsVerkoyenBpostBpack247(
			self::BPACK247_ID,
			self::BPACK247_PASS
		);

		try {
			if (!$xml = call_user_func_array( array($bpack247, $method), $args ))
				$member['Error'] = 'Server or Developer Error !';
			elseif (!isset($xml->DeliveryCode))
				$member['Error'] = $method.': Invalid RC code';

		} catch (TijsVerkoyenBpostException $e) {
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
		$msg_format = 'BpostSHM::Service > '.$func.' - '.$msg;
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
		$setting = (int)Configuration::get('BPOST_INTERNATIONAL_DELIVERY_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id));
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
		if ($options_list = Configuration::get('BPOST_DELIVERY_OPTIONS_LIST_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id)))
		{
			$options_list = json_decode($options_list, true);
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
						$options[] = new TijsVerkoyenBpostBpostOrderBoxOptionSrgSigned();
						break;

					case '330': // 2nd Presentation
						$options[] = new TijsVerkoyenBpostBpostOrderBoxOptionSrgAutomaticSecondPresentation();
						break;

					case '540':
					case '350': // Insurance
						$options[] = new TijsVerkoyenBpostBpostOrderBoxOptionSrgInsured('basicInsurance');
						break;

					case '470': // Saturday delivery (Not yet implemented)
						// $options[] = new TijsVerkoyenBpostBpostOrderBoxOption_XXX();
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
		if (self::isPrestashopFresherThan14())
			return $this->context->link->getModuleLink($module, $controller, $params);
		else
			return _MODULE_DIR_.$module.'/controllers/front/'.$controller.'.php?'.http_build_query($params);
	}

	public function getControllerLink($module, $controller, $params)
	{
		$params['ps14'] = !self::isPrestashopFresherThan14();
		$params['root_dir'] = _PS_ROOT_DIR_;
		return _MODULE_DIR_.$module.'/controllers/front/'.$controller.'.php?'.http_build_query($params);
	}

	/*
	public function getProductConfig()
	{
		$product_config = array();
		try {
			$response = $this->doCall(
				TijsVerkoyenBpostBpost::API_URL.'/'.$this->bpost->getAccountId().'/productconfig',
				null,
				array('Accept: application/vnd.bpost.shm-productConfiguration-v3+XML')
			);
			$product_config = Tools::jsonEncode($response);
			$product_config = Tools::jsonDecode($product_config, true);

		} catch (Exception $e) {
			$product_config['Error'] = 401 === (int)$e->getCode() ? 'Invalid Account ID / Passphrase' : $e->getMessage();

		}

		return $product_config;
	}
	*/

	/**
	 * get full list bpost enabled countries
	 * @return assoc array
	 */
	public function getProductCountries()
	{
		$product_countries_list = 'BE';

		try {
			$xml = $this->doCall(
				TijsVerkoyenBpostBpost::API_URL.'/'.$this->bpost->getAccountId().'/productconfig',
				null,
				array('Accept: application/vnd.bpost.shm-productConfiguration-v3+XML')
			);

			if (!isset($xml->deliveryMethod))
				throw new Exception('No suitable delivery method');
			else
				foreach ($xml->deliveryMethod as $dm)
					foreach ($dm->product as $product)
						if ('bpack World' === mb_substr((string)$product->attributes()->name, 0, 11)
							&& isset($product->price))
						{
							$product_countries = array();
							foreach ($product->price as $price)
								$product_countries[] = (string)$price->attributes()->countryIso2Code;

							if (count($product_countries))
								$product_countries_list = implode('|', $product_countries);

							break;
						}

		} catch (Exception $e) {
			return array('Error' => (401 === (int)$e->getCode()) ? 'Invalid Account ID / Passphrase' : $e->getMessage());

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
		$iso_list = str_replace($glue, "','", $iso_list);
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