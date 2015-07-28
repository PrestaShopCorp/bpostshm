<?php
/**
 * 2014 Stigmi
 *
 * bpost Shipping Manager
 *
 * This controller is used by PrestaShop 1.4 shops
 *
 * @author    Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

const _CONFIG_FILE_ = '../../../../config/config.inc.php';
const _CONFIG_FILE_DEV_ = '../../../../sites/bpost/sps14/config/config.inc.php';
const _CONTROLLER_FILE_ = 'controllers/front/lightbox14.php';

if (file_exists(_CONFIG_FILE_))
	require_once(_CONFIG_FILE_);
elseif (file_exists(_CONFIG_FILE_DEV_))
	require_once(_CONFIG_FILE_DEV_);
else
	die('Cannot locate config');

require_once(_PS_MODULE_DIR_.'bpostshm/bpostshm.php');
require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');

class Lightbox extends FrontController
{
	private $tpl;
	public $ssl = true;

	public function process()
	{
		parent::process();

		$shipping_method = Tools::getValue('shipping_method');
		$token = Tools::getValue('token');

		if (!is_numeric($shipping_method) || $token != Tools::getToken('bpostshm'))
		{
			Tools::redirect('/');
			return;
		}

		$shipping_method = (int)$shipping_method;

		$bpost = new BpostShm();
		$context = Context::getContext();
		$service = new Service($context);

		// Reset selected bpost service point
		$cart_bpost = PsCartBpost::getByPsCartID((int)$context->cart->id);
		$cart_bpost->reset();

		// Looking for AJAX requests
		if (Tools::getValue('get_nearest_service_points'))
		{
			$search_params = array('zone' => '',);
			$postcode = Tools::getValue('postcode');
			$city = Tools::getValue('city');
			if ($postcode)
				$search_params['zone'] .= (int)$postcode.($city ? ' ' : '');
			if ($city)
				$search_params['zone'] .= (string)$city;

			$service_points = (BpostShm::SHM_PPOINT == $shipping_method) ?
				$service->getNearestServicePoint($search_params) :
				$service->getNearestServicePoint($search_params, $shipping_method);
			$this->jsonEncode($service_points);
		}
		elseif (Tools::getValue('get_service_point_hours'))
		{
			$service_point_id = (int)Tools::getValue('service_point_id');
			$sp_type = (int)Tools::getValue('sp_type');
			$service_point_hours = $service->getServicePointHours($service_point_id, $sp_type);
			$this->jsonEncode($service_point_hours);
		}
		elseif (Tools::getValue('set_service_point'))
		{
			$service_point_id = (int)Tools::getValue('service_point_id');
			$sp_type = (int)Tools::getValue('sp_type');
			$this->jsonEncode($cart_bpost->setServicePoint($service_point_id, $sp_type));
		}
		elseif (Tools::getValue('post_upl_unregister'))
		{
			$upl_info = (string)Tools::getValue('post_upl_info');
			$stored = $upl_info === (string)$cart_bpost->upl_info;
			if (!$stored)
			{
				$cart_bpost->upl_info = $upl_info;
				$stored = $cart_bpost->save();
			}

			$this->jsonEncode($stored);
		}
		/*
		elseif (Tools::getValue('get_bpack247_member'))
		{
			$rcn = Tools::getValue('rcn');
			$member = $service->getBpack247Member($rcn, 'Number, Street, Town, Postalcode, PackstationID, DeliveryCode');
			$this->validateStore($member, $cart_bpost);
		}
		elseif (Tools::getValue('post_bpack247_register'))
		{
			$customer = array();
			if ($id_gender = (int)Tools::getValue('id_gender'))
				switch ($id_gender)
				{
					case 1:
					case 9:
					default:
						$customer['Title'] = 'Mr.';
						break;

					case 2:
						$customer['Title'] = 'Ms.';
						break;
				}

			if ($firstname = (string)Tools::getValue('firstname'))
				$customer['FirstName'] = $firstname;
			if ($lastname = (string)Tools::getValue('lastname'))
				$customer['LastName'] = $lastname;
			if ($street = (string)Tools::getValue('street'))
				$customer['Street'] = $street;
			if ($nr = (int)Tools::getValue('number'))
				$customer['Number'] = $nr;
			if ($postal_code = (int)Tools::getValue('postal_code'))
				$customer['Postalcode'] = $postal_code;
			if ($town = (string)Tools::getValue('town'))
				$customer['Town'] = $town;
			if ($date_of_birth = (string)Tools::getValue('date_of_birth'))
				$customer['DateOfBirth'] = $date_of_birth;
			if ($email = (string)Tools::getValue('email'))
				$customer['Email'] = Tools::strtoupper($email);
			if ($mobile_number = (string)Tools::getValue('mobile_number'))
				// int cast removes leading zero
				// * Srg: int is the least of the problems. proper RE validation already done
				$customer['MobileNumber'] = (int)$mobile_number;
			if ($preferred_language = (string)Tools::getValue('preferred_language'))
				$customer['PreferredLanguage'] = $preferred_language;

			$member = $service->createBpack247Member($customer, 'Number, Street, Postalcode, DeliveryCode');
			$this->validateStore($member, $cart_bpost);
		}
		*/
		// Building display page
		self::$smarty->assign('version', (Service::isPrestashop16plus() ? 1.6 : (Service::isPrestashop15plus() ? 1.5 : 1.4)), true);

		switch ($shipping_method)
		{
			case BpostShm::SHM_PPOINT:
				self::$smarty->assign('module_dir', _MODULE_DIR_.'bpostshm/');
				self::$smarty->assign('shipping_method', $shipping_method, true);

				// $delivery_address = new Address($context->cart->id_address_delivery, $context->language->id);

				// self::$smarty->assign('city', $delivery_address->city, true);
				// self::$smarty->assign('postcode', $delivery_address->postcode, true);

				// $search_params = array(
				// 	'street' 	=> '',
				// 	'nr' 		=> '',
				// 	'zone'		=> $delivery_address->postcode.' '.$delivery_address->city,
				// );
				// $service_points = $service->getNearestServicePoint($search_params/*, $shipping_method*/);
				// if (empty($service_points))
				// {
				// 	$search_params['zone'] = $delivery_address->postcode;
				// 	$service_points = $service->getNearestServicePoint($search_params/*, $shipping_method*/);
				// }
				// self::$smarty->assign('servicePoints', $service_points, true);
				$named_fields = $service->getNearestValidServicePoint();
				foreach ($named_fields as $name => $field)
					self::$smarty->assign($name, $field, true);

				self::$smarty->assign('url_get_nearest_service_points', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
					.http_build_query(array(
						'ajax'							=> true,
						'get_nearest_service_points' 	=> true,
						'shipping_method'				=> $shipping_method,
						'token'							=> Tools::getToken('bpostshm'),
					)));
				self::$smarty->assign('url_get_service_point_hours', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
					.http_build_query(array(
						'ajax'						=> true,
						'get_service_point_hours' 	=> true,
						'shipping_method'			=> $shipping_method,
						'token'						=> Tools::getToken('bpostshm'),
					)));
				self::$smarty->assign('url_set_service_point', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
					.http_build_query(array(
						'ajax'				=> true,
						'set_service_point' => true,
						'shipping_method'	=> $shipping_method,
						'token'				=> Tools::getToken('bpostshm'),
					)));

				$this->setTemplate('lightbox-point-list.tpl');
				break;

			case BpostShm::SHM_PLOCKER:
				$step = (int)Tools::getValue('step', 1);
				switch ($step)
				{
					default:
					case 1:
						self::$smarty->assign('module_dir', _MODULE_DIR_.'bpostshm/');
						self::$smarty->assign('shipping_method', $shipping_method, true);
						self::$smarty->assign('step', 1, true);

						$delivery_address = new Address($context->cart->id_address_delivery, $context->language->id);
						// UPL
						$upl_info = Tools::jsonDecode($cart_bpost->upl_info, true);
						if (!isset($upl_info))
							$upl_info = array(
								'eml' => $context->customer->email,
								'mob' => !empty($delivery_address->phone_mobile) ? $delivery_address->phone_mobile : '',
								'rmz' => false,
								);

						$iso_code = $context->language->iso_code;
						$upl_info['lng'] = in_array($iso_code, array('fr', 'nl')) ? $iso_code : 'en';
						self::$smarty->assign('upl_info', $upl_info, true);
						//
						/*
						self::$smarty->assign('gender', $context->customer->id_gender);
						self::$smarty->assign('genders', array(
							(object)array('id' => 1, 'name' => 'Mr'),
							(object)array('id' => 2, 'name' => 'Ms'),
							(object)array('id' => 9, 'name' => 'Mr'),
						));
						self::$smarty->assign('firstname', $delivery_address->firstname, true);
						self::$smarty->assign('lastname', $delivery_address->lastname, true);

						$address = $service->getAddressStreetNr(
							array(
								'nr' => '',
								'street' => $delivery_address->address1,
								'line2' => $delivery_address->address2,
								));
						self::$smarty->assign('street', $address['street'], true);
						self::$smarty->assign('number', $address['nr'], true);

						self::$smarty->assign('postal_code', $delivery_address->postcode, true);
						self::$smarty->assign('locality', $delivery_address->city, true);
						self::$smarty->assign('birthday',
							'0000-00-00' != $context->customer->birthday ? $context->customer->birthday : '', true);
						self::$smarty->assign('email', $context->customer->email, true);
						self::$smarty->assign('mobile_phone',
							!empty($delivery_address->phone) ? $delivery_address->phone : $delivery_address->phone_mobile, true);
						self::$smarty->assign('language', $context->language->iso_code, true);
						self::$smarty->assign('languages', array(
							'en' 	=> array(
								'lang' 	=> 'en-US',
								'name' 	=> $bpost->l('English'),
							),
							'fr' 	=> array(
								'lang' 	=> 'fr-BE',
								'name' 	=> $bpost->l('French'),
							),
							'nl' 	=> array(
								'lang' 	=> 'nl-BE',
								'name' 	=> $bpost->l('Dutch'),
							),
						));
						*/
						self::$smarty->assign('url_post_upl_unregister', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
							.http_build_query(array(
								'ajax'					=> true,
								'post_upl_unregister' 	=> true,
								'shipping_method'		=> $shipping_method,
								'token'					=> Tools::getToken('bpostshm'),
							)));
						/*
						self::$smarty->assign('url_post_bpack247_register', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
							.http_build_query(array(
								'ajax'						=> true,
								'post_bpack247_register' 	=> true,
								'shipping_method'			=> $shipping_method,
								'token'						=> Tools::getToken('bpostshm'),
							)));
						*/
						self::$smarty->assign('url_get_point_list', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
							.http_build_query(array(
								'content_only'		=> true,
								'shipping_method'	=> $shipping_method,
								'step'				=> 2,
								'token'				=> Tools::getToken('bpostshm'),
							)));
						/*
						self::$smarty->assign('url_get_bpack247_member', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
							.http_build_query(array(
							'ajax'					=> true,
							'get_bpack247_member'	=> true,
							'shipping_method'		=> $shipping_method,
							'token'					=> Tools::getToken('bpostshm'),
						)));
						*/
						$this->setTemplate('lightbox-at-247.tpl');
						break;

					case 2:
						self::$smarty->assign('module_dir', _MODULE_DIR_.'bpostshm/');
						self::$smarty->assign('shipping_method', $shipping_method, true);
/*
						if (!$customer = $cart_bpost->bpack247_customer)
							return false;

						$customer = Tools::jsonDecode($customer, true);

						$focus_point = array(
							'Street'		=> $customer['Street'],
							'Number'		=> $customer['Number'],
							'Postalcode' 	=> $customer['Postalcode'],
							);
						if (isset($customer['Town']))
							$focus_point['Town'] = $customer['Town'];

						if (!empty($customer['PackstationID']))
						{
							$packstation_id = $customer['PackstationID'];
							self::$smarty->assign('defaultStation', sprintf('%06s', $packstation_id), true);
							$service_point_details = $service->getServicePointDetails($packstation_id, $shipping_method);
							if (!empty($service_point_details))
							{
								$focus_point['Street'] = $service_point_details['street'];
								$focus_point['Number'] = $service_point_details['nr'];
								$focus_point['Postalcode'] = $service_point_details['zip'];
								$focus_point['Town'] = $service_point_details['city'];
							}
						}
						$zone = $focus_point['Postalcode'];
						self::$smarty->assign('postcode', $focus_point['Postalcode'], true);
						if (!empty($focus_point['Town']))
						{
							self::$smarty->assign('city', $focus_point['Town'], true);
							$zone .= ' '.$focus_point['Town'];
						}

						$search_params = array(
							'street' 	=> $focus_point['Street'],
							'nr' 		=> $focus_point['Number'],
							'zone'		=> $zone,
						);
						$service_points = $service->getNearestServicePoint($search_params, $shipping_method);
						if (empty($service_points))
						{
							$search_params['zone'] = $focus_point['Postalcode'];
							$service_points = $service->getNearestServicePoint($search_params, $shipping_method);
						}

						self::$smarty->assign('servicePoints', $service_points, true);
*/
						$named_fields = $service->getNearestValidServicePoint($shipping_method);
						foreach ($named_fields as $name => $field)
							self::$smarty->assign($name, $field, true);

						self::$smarty->assign('url_get_nearest_service_points', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
							.http_build_query(array(
								'ajax'							=> true,
								'get_nearest_service_points' 	=> true,
								'shipping_method'				=> $shipping_method,
								'token'							=> Tools::getToken('bpostshm'),
							)));
						self::$smarty->assign('url_get_service_point_hours', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
							.http_build_query(array(
								'ajax'						=> true,
								'get_service_point_hours' 	=> true,
								'shipping_method'			=> $shipping_method,
								'token'						=> Tools::getToken('bpostshm'),
							)));
						self::$smarty->assign('url_set_service_point', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
							.http_build_query(array(
								'ajax'				=> true,
								'set_service_point' => true,
								'shipping_method'	=> $shipping_method,
								'token'				=> Tools::getToken('bpostshm'),
							)));

						$this->setTemplate('lightbox-point-list.tpl');
						break;
				}
				break;
		}
	}

	public function displayContent()
	{
		parent::displayContent();

		self::$smarty->display($this->tpl);
	}

	public function displayHeader()
	{
		if (!Tools::getValue('ajax', false))
			echo '
				<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
				<script src="'._MODULE_DIR_.'bpostshm/views/js/bpostshm.js" type="text/javascript"></script>
				<script src="'._MODULE_DIR_.'bpostshm/views/js/srgdebug.js" type="text/javascript"></script>
				<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-scrollTo/1.4.11/jquery.scrollTo.min.js" type="text/javascript"></script>
				<script src="//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.pack.js" type="text/javascript"></script>
				<script src="'._MODULE_DIR_.'bpostshm/views/js/jquery.qtip.min.js" type="text/javascript"></script>
				<script src="https://maps.googleapis.com/maps/api/js?v=3.16&key='.Service::GMAPS_API_KEY.'&sensor=false&language=fr"
					type="text/javascript"></script>
				<link href="'._THEME_CSS_DIR_.'global.css" type="text/css" rel="stylesheet" />
				<link href="'._MODULE_DIR_.'bpostshm/views/css/lightbox.css" type="text/css" rel="stylesheet" />
				<link href="'._MODULE_DIR_.'bpostshm/views/css/jquery.qtip.min.css" type="text/css" rel="stylesheet" />
				<link href="//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.css" type="text/css" rel="stylesheet" />';

	}

	public function setMedia()
	{
		parent::setMedia();

		Tools::addCSS((_PS_SSL_ENABLED_ ? 'https://' : 'http://').'//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.css', 'screen');
		Tools::addCSS(__PS_BASE_URI__.'/modules/bpostshm/views/css/lightbox.css');
		Tools::addCSS(__PS_BASE_URI__.'/modules/bpostshm/views/css/jquery.qtip.min.css');

		Tools::addJS((_PS_SSL_ENABLED_ ? 'https://' : 'http://').'//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
		Tools::addJS(__PS_BASE_URI__.'/modules/bpostshm/views/js/bpostshm.js');
		Tools::addJS(__PS_BASE_URI__.'/modules/bpostshm/views/js/srgdebug.js');
		Tools::addJS((_PS_SSL_ENABLED_ ? 'https://' : 'http://').'//cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.pack.js');
		Tools::addJS(__PS_BASE_URI__.'/modules/bpostshm/views/js/jquery.qtip.min.js');
		Tools::addJS('https://maps.googleapis.com/maps/api/js?v=3.16&key='.Service::GMAPS_API_KEY.'&sensor=false&language=fr');
	}

	private function validateStore($member, $cart_bpost = null)
	{
		$json_member = (string)Tools::jsonEncode($member);
		if (!isset($member['Error']))
			try {
				$cart_bpost->bpack247_customer = $json_member;
				$cart_bpost->update();

			} catch (\Exception $e) {
				$json_member = Tools::jsonEncode(array('Error' => $e->getMessage()));
			}

		$this->terminateWith($json_member);
	}

	private function terminateWith($json)
	{
		header('Content-Type: application/json');
		die($json);
	}

	private function jsonEncode($content)
	{
		header('Content-Type: application/json');
		die(Tools::jsonEncode($content));
	}

	private function setTemplate($tpl)
	{
		$this->tpl = _PS_MODULE_DIR_.'bpostshm/views/templates/front/'.$tpl;
	}
}

$controller = new Lightbox();
$controller->init();
$controller->preProcess();
$controller->displayHeader();
$controller->process();
$controller->displayContent();

