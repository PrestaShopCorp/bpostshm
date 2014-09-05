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

require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');

class BpostShmLightboxModuleFrontController extends ModuleFrontController
{
	const GEO6_PARTNER = 999999;
	const GEO6_APP_ID = '';

	public function initContent()
	{
		$shipping_method = Tools::getValue('shipping_method');
		$token = Tools::getValue('token');

		if (!is_numeric($shipping_method) || $token != Tools::getToken('bpostshm'))
		{
			Tools::redirect('/');
			return;
		}

		parent::initContent();

		$service = new Service($this->context);

		// Reset selected bpost service point
		$this->context->cart->service_point_id = 0;
		$this->context->cart->update();

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

			$service_points = $service->getNearestServicePoint($search_params, $shipping_method);
			$this->jsonEncode($service_points);
		}
		elseif (Tools::getValue('get_service_point_hours') && $service_point_id = (int)Tools::getValue('service_point_id'))
		{
			$service_point_hours = $service->getServicePointHours($service_point_id, $shipping_method);
			$this->jsonEncode($service_point_hours);
		}
		elseif (Tools::getValue('set_service_point') && $service_point_id = (int)Tools::getValue('service_point_id'))
		{
			$this->context->cart->service_point_id = $service_point_id;
			$this->jsonEncode($this->context->cart->update());
		}
		elseif (Tools::getValue('get_bpack247_member'))
		{
			$rcn = Tools::getValue('rcn');
			$member = $service->getBpack247Member($rcn, 'Number, Street, Town, Postalcode, PackstationID, DeliveryCode');
			$this->validateStore($member);
		}
		elseif (Tools::getValue('post_bpack247_register'))
		{
			$customer = array();
			if ($id_gender = (int)Tools::getValue('id_gender'))
			{
				$gender = new Gender($id_gender, $this->context->language->id, $this->context->shop->id);
				if ($gender->type)
					$customer['Title'] = 'Ms.';
				else
					$customer['Title'] = 'Mr.';
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

//$customer['Error'] = 'Registering: is this correct?';
//$this->jsonEncode($customer);

			$member = $service->createBpack247Member($customer, 'Number, Street, Postalcode, DeliveryCode');
			$this->validateStore($member);
		}
		

		// Building display page
		self::$smarty->assign('version', (Service::isPrestashop16() ? 1.6 : (Service::isPrestashopFresherThan14() ? 1.5 : 1.4)), true);

		switch ($shipping_method)
		{
			case BpostShm::SHIPPING_METHOD_AT_SHOP:
				self::$smarty->assign('module_dir', _MODULE_DIR_.$this->module->name.'/');
				self::$smarty->assign('shipping_method', $shipping_method, true);

				$delivery_address = new Address($this->context->cart->id_address_delivery, $this->context->language->id);

				self::$smarty->assign('city', $delivery_address->city, true);
				self::$smarty->assign('postcode', $delivery_address->postcode, true);

				$search_params = array(
					'street' 	=> '',
					'nr' 		=> '',
					'zone'		=> $delivery_address->postcode.' '.$delivery_address->city,
				);
				$service_points = $service->getNearestServicePoint($search_params, $shipping_method);
				self::$smarty->assign('servicePoints', $service_points, true);

				self::$smarty->assign('url_get_nearest_service_points', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
					'ajax'							=> true,
					'get_nearest_service_points' 	=> true,
					'shipping_method'				=> $shipping_method,
					'token'							=> Tools::getToken('bpostshm'),
				)));
				self::$smarty->assign('url_get_service_point_hours', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
					'ajax'						=> true,
					'get_service_point_hours' 	=> true,
					'shipping_method'			=> $shipping_method,
					'token'						=> Tools::getToken('bpostshm'),
				)));
				self::$smarty->assign('url_set_service_point', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
					'ajax'				=> true,
					'set_service_point' => true,
					'shipping_method'	=> $shipping_method,
					'token'				=> Tools::getToken('bpostshm'),
				)));

				$this->addJqueryPlugin('scrollTo');
				$this->setTemplate('lightbox-point-list.tpl');
				break;

			case BpostShm::SHIPPING_METHOD_AT_24_7:
				$step = (int)Tools::getValue('step', 1);
				switch ($step)
				{
					default:
					case 1:
						self::$smarty->assign('module_dir', _MODULE_DIR_.$this->module->name.'/');
						self::$smarty->assign('shipping_method', $shipping_method, true);
						self::$smarty->assign('step', 1, true);

						$delivery_address = new Address($this->context->cart->id_address_delivery, $this->context->language->id);

						self::$smarty->assign('gender', $this->context->customer->id_gender);
						self::$smarty->assign('genders', Gender::getGenders($this->context->language->id));
						self::$smarty->assign('firstname', $delivery_address->firstname, true);
						self::$smarty->assign('lastname', $delivery_address->lastname, true);

						preg_match('#([0-9]+)?[, ]*([\p{L}a-zA-Z -]+)[, ]*([0-9]+)?#iu', $delivery_address->address1, $matches);
						if (!empty($matches[1]) && is_numeric($matches[1]))
							$nr = $matches[1];
						elseif (!empty($matches[3]) && is_numeric($matches[3]))
							$nr = $matches[3];
						else
							$nr = (!empty($delivery_address->address2) && is_numeric($delivery_address->address2) ? $delivery_address->address2 : '');
						$street = !empty($matches[2]) ? $matches[2] : $delivery_address->address1;

						self::$smarty->assign('street', $street, true);
						self::$smarty->assign('number', $nr, true);

						self::$smarty->assign('postal_code', $delivery_address->postcode, true);
						self::$smarty->assign('locality', $delivery_address->city, true);
						self::$smarty->assign('birthday',
							'0000-00-00' != $this->context->customer->birthday ? $this->context->customer->birthday : '', true);
						self::$smarty->assign('email', $this->context->customer->email, true);
						self::$smarty->assign('mobile_phone',
							!empty($delivery_address->phone) ? $delivery_address->phone : $delivery_address->phone_mobile, true);
						self::$smarty->assign('language', $this->context->language->iso_code, true);
						self::$smarty->assign('languages', array(
							'en' 	=> array(
								'lang' 	=> 'en-US',
								'name' 	=> $this->module->l('English'),
							),
							'fr' 	=> array(
								'lang' 	=> 'fr-BE',
								'name' 	=> $this->module->l('French'),
							),
							'nl' 	=> array(
								'lang' 	=> 'nl-BE',
								'name' 	=> $this->module->l('Dutch'),
							),
						));

						self::$smarty->assign('url_post_bpack247_register', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'						=> true,
							'post_bpack247_register' 	=> true,
							'shipping_method'			=> $shipping_method,
							'token'						=> Tools::getToken('bpostshm'),
						)));

						self::$smarty->assign('url_get_point_list', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'content_only'		=> true,
							'shipping_method'	=> $shipping_method,
							'step'				=> 2,
							'token'				=> Tools::getToken('bpostshm'),
						)));

						self::$smarty->assign('url_get_bpack247_member', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'					=> true,
							'get_bpack247_member'	=> true,
							'shipping_method'		=> $shipping_method,
							'token'					=> Tools::getToken('bpostshm'),
						)));

						$this->addJqueryPlugin('fancybox');
						$this->setTemplate('lightbox-at-247.tpl');
						break;

					case 2:
						self::$smarty->assign('module_dir', _MODULE_DIR_.$this->module->name.'/');
						self::$smarty->assign('shipping_method', $shipping_method, true);

						
						if (!$customer = $this->context->cart->bpack247_customer)
							return false;

						$customer = Tools::jsonDecode($customer, true);

						$zone = $customer['Postalcode'];
						self::$smarty->assign('postcode', $customer['Postalcode'], true);
						if (!empty($customer['Town']))
						{
							self::$smarty->assign('city', $customer['Town'], true);
							$zone .= ' '.$customer['Town'];
						}
						if (!empty($customer['PackstationID']))
							self::$smarty->assign('defaultStation', sprintf("%06s", $customer['PackstationID']), true);

						$search_params = array(
							'street' 	=> $customer['Street'],
							'nr' 		=> $customer['Number'],
							'zone'		=> $zone, //$customer['Town'],
						);
						$service_points = $service->getNearestServicePoint($search_params, $shipping_method);
						self::$smarty->assign('servicePoints', $service_points, true);
						
						self::$smarty->assign('url_get_nearest_service_points', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'							=> true,
							'get_nearest_service_points' 	=> true,
							'shipping_method'				=> $shipping_method,
							'token'							=> Tools::getToken('bpostshm'),
						)));
						self::$smarty->assign('url_get_service_point_hours', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'						=> true,
							'get_service_point_hours' 	=> true,
							'shipping_method'			=> $shipping_method,
							'token'						=> Tools::getToken('bpostshm'),
						)));
						self::$smarty->assign('url_set_service_point', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'				=> true,
							'set_service_point' => true,
							'shipping_method'	=> $shipping_method,
							'token'				=> Tools::getToken('bpostshm'),
						)));

						$this->addJqueryPlugin('scrollTo');
						$this->setTemplate('lightbox-point-list.tpl');
						break;
				}
				break;
		}
	}

	public function setMedia()
	{
		parent::setMedia();

		$this->addCSS(__PS_BASE_URI__.'modules/'.$this->module->name.'/views/css/lightbox.css');
		$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/views/js/bpostshm.js');
		$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/views/js/srgdebug.js');
		$this->addJS('https://maps.googleapis.com/maps/api/js?v=3.16&key=AIzaSyAa4S8Br_5of6Jb_Gjv1WLldkobgExB2KY&sensor=false&language='
			.$this->context->language->iso_code);
	}

	private function validateStore($member)
	{
		$json_member = Tools::jsonEncode($member);
		
		// Better to store the JSON string. serializing fails everytime
		// Special NOTE: Cart.php override has changed to reflect this ('isSerializedArray' => 'isString')	
		if (!isset($member['Error']))
			try {
				$this->context->cart->bpack247_customer = $json_member;
				$this->context->cart->update();
			
			} catch (Exception $e) {
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

}