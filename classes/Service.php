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
	/**
	 * @var Service
	 */
	protected static $instance;

	/* Cache fetched orders */
	public static $cache = array();

	public $bpost;
	private $context;
	private $geo6;

	const GEO6_PARTNER = 999999;
	const GEO6_APP_ID = '';

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

	public static function isPrestashopFresherThan14()
	{
		return version_compare(_PS_VERSION_, '1.5', '>=');
	}

	public static function isPrestashop16()
	{
		return version_compare(_PS_VERSION_, '1.6', '>');
	}

	public function getProductConfig()
	{
		$product_config = array();

		if ($response = $this->doCall(
				TijsVerkoyenBpostBpost::API_URL.'/'.$this->bpost->getAccountId().'/productconfig',
				null,
				array(
					'Accept: application/vnd.bpost.shm-productConfiguration-v3+XML',
				)
			))
		{
			$product_config = Tools::jsonEncode($response);
			$product_config = Tools::jsonDecode($product_config, true);
		}

		return $product_config;
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
	 * @param array $params
	 */
	public function makerBpack247Customer($params = array())
	{
		$customer = new TijsVerkoyenBpostBpack247Customer();

		if (isset($params['user_id']) && $params['user_id'] != '')
			$customer->setUserID((string)$params['user_id']);
		if (isset($params['firstname']) && $params['firstname'] != '')
			$customer->setFirstName((string)$params['firstname']);
		if (isset($params['lastname']) && $params['lastname'] != '')
			$customer->setLastName((string)$params['lastname']);
		if (isset($params['street']) && $params['street'] != '')
			$customer->setStreet((string)$params['street']);
		if (isset($params['number']) && $params['number'] != '')
			$customer->setNumber((string)$params['number']);
		if (isset($params['company']) && $params['company'] != '')
			$customer->setCompanyName((string)$params['company']);
		if (isset($params['date_of_birth']) && $params['date_of_birth'] != '')
		{
			$date_time = new \DateTime((string)$params['date_of_birth']);
			$customer->setDateOfBirth($date_time);
		}
		if (isset($params['delivery_code']) && $params['delivery_code'] != '')
			$customer->setDeliveryCode((string)$params['delivery_code']);
		if (isset($params['email']) && $params['email'] != '')
			$customer->setEmail((string)$params['email']);
		if (isset($params['mobile_prefix']) && $params['mobile_prefix'] != '')
			$customer->setMobilePrefix(trim((string)$params['mobile_prefix']));
		if (isset($params['mobile_number']) && $params['mobile_number'] != '')
			$customer->setMobileNumber((string)$params['mobile_number']);
		if (isset($params['postal_code']) && $params['postal_code'] != '')
			$customer->setPostalCode((string)$params['postal_code']);
		if (isset($params['preferred_language']) && $params['preferred_language'] != '')
			$customer->setPreferredLanguage((string)$params['preferred_language']);
		if (isset($params['receive_promotions']) && $params['receive_promotions'] != '')
		{
			$receive_promotions = in_array((string)$params['receive_promotions'], array('true', '1'));
			$customer->setReceivePromotions($receive_promotions);
		}
		if (isset($params['actived']) && $params['actived'] != '')
		{
			$activated = in_array((string)$params['actived'], array('true', '1'));
			$customer->setActivated($activated);
		}
		if (isset($params['title']) && $params['title'] != '')
		{
			$title = (string)$params['title'];
			$title = Tools::ucfirst(Tools::strtolower($title));
			if (Tools::substr($title, -1) != '.')
				$title .= '.';

			$customer->setTitle($title);
		}
		if (isset($params['town']) && $params['town'] != '')
			$customer->setTown((string)$params['town']);

		$bpack247 = new TijsVerkoyenBpostBpack247(
			Configuration::get('BPOST_ACCOUNT_ID_'.$this->context->shop->id),
			Configuration::get('BPOST_ACCOUNT_PASSPHRASE_'.$this->context->shop->id)
		);

		$response = true;

		try {
			$response = $response && $bpack247->createMember($customer);
		} catch (TijsVerkoyenBpostException $e) {
			$response = false;
		}

		return $response;
	}

	/**
	 * @param array $params
	 * @param int $type
	 * @param bool $is_retour
	 * @return TijsVerkoyenBpostBpostOrder
	 */
	public function makeOrder($id_order = 0, $type = 3, $is_retour = false)
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
		else
			$reference = Configuration::get('BPOST_ACCOUNT_ID_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id)).'_'
				.Tools::substr($ps_order->id, 0, 53);

		$order = new TijsVerkoyenBpostBpostOrder($reference);
		//$order->setCostCenter('Cost Center');

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
		if ((int)$type == (int)BpostShm::SHIPPING_METHOD_AT_SHOP)
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
					if ('bpack Easy Retour' != $national_box->getProduct())
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

						$response = $response && $this->createPSLabel($reference);
					}
		}
		else
		{
			$shippers = $this->getReceiverAndSender($ps_order);
			$response = $response && $this->addBox($order, (int)$type, $shippers['sender'], $shippers['receiver'], $weight, $service_point_id);
			$response = $response && $this->createPSLabel($reference);
		}

		try {
			$response = $response && $this->bpost->createOrReplaceOrder($order);
			exit;
			//$response &= $this->updateOrderStatus($reference);
			//$response &= $this->bpost->modifyOrderStatus($order->getReference(), 'OPEN');
		} catch (TijsVerkoyenBpostException $e) {
			$response = false;
		}

		return $response;
	}

	/**
	 * @param TijsVerkoyenBpostBpostOrder $order
	 * @param int $type
	 * @param TijsVerkoyenBpostBpostOrderSender $sender
	 * @param TijsVerkoyenBpostBpostOrderReceiver $receiver
	 * @param int $weight
	 * @param null $service_point_id
	 * @param TijsVerkoyenBpostBpostOrderBox $box
	 * @return bool
	 */
	public function addBox(TijsVerkoyenBpostBpostOrder $order = null, $type = 0, TijsVerkoyenBpostBpostOrderSender $sender,
		TijsVerkoyenBpostBpostOrderReceiver $receiver, $weight = 0, $service_point_id = null, $box = null)
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
					$customsInfo = new TijsVerkoyenBpostBpostOrderBoxCustomsinfoCustomsInfo();
					$customsInfo->setParcelValue((float)$ps_order->total_paid * 100);
					$customsInfo->setContentDescription('ORDER '.Configuration::get('PS_SHOP_NAME'));
					$customsInfo->setShipmentType('DOCUMENTS');
					$customsInfo->setParcelReturnInstructions('RTS');
					$customsInfo->setPrivateAddress(false);

					$international = new TijsVerkoyenBpostBpostOrderBoxInternational();
					$international->setProduct('bpack World Express Pro');
					$international->setReceiver($receiver);
					$international->setParcelWeight($weight);
					$international->setCustomsInfo($customsInfo);

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

						$option = new TijsVerkoyenBpostBpostOrderBoxOptionMessaging(
							'infoDistributed',
							$this->context->language->iso_code,
							$sender->getEmailAddress()
						);
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

				$option = new TijsVerkoyenBpostBpostOrderBoxOptionMessaging(
					'keepMeInformed',
					$this->context->language->iso_code,
					$sender->getEmailAddress()
				);
				$at_bpost->addOption($option);

				$box->setNationalBox($at_bpost);
				break;

			/*case (int)BpostShm::SHIPPING_METHOD_AT_24_7:
				// @24/7
				$parcels_depot_address = new ParcelsDepotAddress(
					'Turnhoutsebaan',
					'468',
					null,
					'2110',
					'Wijnegem',
					'BE'
				);
				$parcels_depot_address->setBox('A');

				$at247 = new At247();
				$at247->setParcelsDepotId('014472');
				$at247->setParcelsDepotName('WIJNEGEM');
				$at247->setParcelsDepotAddress($parcels_depot_address);
				$at247->setMemberId('188565346');
				$at247->setReceiverName('Tijs Verkoyen');
				$at247->setReceiverCompany('Sumo Coders');
				$box->setNationalBox($at247);
				break;
			*/
		}

		$order->addBox($box);

		return $response;
	}

	/**
	 * @param string|null $reference
	 * @return string
	 */
	public function getOrderRecipient($reference = null)
	{
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
							if ('bpack Easy Retour' == $national_box->getProduct())
								continue;

							if (method_exists($national_box, 'getReceiver'))
							{
								$receiver = $national_box->getReceiver();
								$address = $receiver->getAddress();
								$recipient = $receiver->getName().' '.$address->getStreetName().' '.$address->getNumber().' '
									.$address->getPostalCode().' '.$address->getLocality();
							}
							elseif ($address = $national_box->getPugoAddress())
								$recipient = $national_box->getReceiverName().' '.$national_box->getPugoName().' '.$address->getPostalCode().' '
									.$address->getLocality();
						}
			} catch (TijsVerkoyenBpostException $e) {
				$recipient = '-';
			}
		}
		else
			$recipient = '-';

		return $recipient;
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
	public function createPSLabel($reference = '', $status = 'PENDING')
	{
		$response = true;

		if (empty($reference) || empty($status))
			return !$response;

		$reference = Tools::substr($reference, 0, 50);
		$response &= Db::getInstance()->execute('
INSERT INTO
	'._DB_PREFIX_.'order_label
(
	`reference`,
	`status`,
	`date_add`
)
VALUES(
	"'.pSQL($reference).'",
	"'.pSQL($status).'",
	NOW()
)');

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

		return Db::getInstance()->execute('
UPDATE
	'._DB_PREFIX_.'order_label
SET
	`status` = "'.pSQL($status).'"
WHERE
	`reference` = "'.pSQL($reference).'"');
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
	 * @return bool
	 */
	public function cleanPSLabels()
	{
		return Db::getInstance()->execute('
DELETE FROM
	'._DB_PREFIX_.'order_label
WHERE
	`product_quantity` < 1');
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
	 * @param Order $ps_order
	 * @param bool $is_retour
	 * @return array
	 */
	public function getReceiverAndSender($ps_order, $is_retour = false)
	{
		$customer = new Customer((int)$ps_order->id_customer);
		$delivery_address = new Address($ps_order->id_address_delivery, $this->context->language->id);

		$shippers = array(
			'client' => array(
				'address1' 	=> $delivery_address->address1,
				'address2' 	=> $delivery_address->address2,
				'city' 		=> $delivery_address->city,
				'email'		=> $customer->email,
				'id_country'=> $delivery_address->id_country,
				'name'		=> $customer->firstname.' '.$customer->lastname,
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
			$sender = $shippers['client'];
			$receiver = $shippers['shop'];
		}

		// create $bpost_sender
		preg_match('#([0-9]+)?[, ]*([a-zA-Z ]+)[, ]*([0-9]+)?#i', $sender['address1'], $matches);
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
		preg_match('#([0-9]+)?[, ]*([a-zA-Z ]+)[, ]*([0-9]+)?#i', $receiver['address1'], $matches);
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

		return array(
			'receiver' => $bpost_receiver,
			'sender' => $bpost_sender,
		);
	}

	/**
	 * @param int $id_carrier
	 * @param bool $slug
	 * @return mixed|string
	 */
	public function getOrderShippingMethod($id_carrier = 0, $slug = true)
	{
		$shipping_method = '';

		if ($id_reference = Db::getInstance()->getValue('
SELECT
	MAX(occ.`id_carrier`)
FROM
	`'._DB_PREFIX_.'carrier` oc
LEFT JOIN
	`'._DB_PREFIX_.'carrier` occ
ON
	occ.`id_reference` = oc.`id_reference`
WHERE
	oc.`id_carrier` = '.(int)$id_carrier))
		{
			$shipping_method = $this->delivery_methods_list[(int)$id_reference];
			if (!$slug)
				$shipping_method = array_search($this->delivery_methods_list[(int)$id_reference], $this->delivery_methods_list);
		}

		return $shipping_method;
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

			$response = $response && $this->addBox($order, (int)$type, $shippers['sender'], $shippers['receiver'], 0, $cart->service_point_id, $box);
			$response = $response && $this->createPSLabel($order->getReference());
		}

		if (!$retour_only)
		{
			$shippers = $this->getReceiverAndSender($ps_order);
			$response = $response && $this->addBox($order, (int)$type, $shippers['sender'], $shippers['receiver'], 0, $cart->service_point_id);
			$response = $response && $this->createPSLabel($order->getReference());
		}

		return $response && $this->bpost->createOrReplaceOrder($order);
	}
}