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
	public $ssl = true;

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
		$cart_bpost = PsCartBpost::getByPsCartID((int)$this->context->cart->id);
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

		// Building display page
		self::$smarty->assign('version', (Service::isPrestashop16plus() ? 1.6 : (Service::isPrestashop15plus() ? 1.5 : 1.4)), true);
		switch ($shipping_method)
		{
			case BpostShm::SHM_PPOINT:
				self::$smarty->assign('module_dir', _MODULE_DIR_.$this->module->name.'/');
				self::$smarty->assign('shipping_method', $shipping_method, true);

				$named_fields = $service->getNearestValidServicePoint();
				foreach ($named_fields as $name => $field)
					self::$smarty->assign($name, $field, true);

				self::$smarty->assign('url_get_nearest_service_points', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
					'ajax'							=> true,
					'get_nearest_service_points' 	=> true,
					'shipping_method'				=> $shipping_method,
					'token'							=> Tools::getToken('bpostshm'),
				), $this->ssl));
				self::$smarty->assign('url_get_service_point_hours', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
					'ajax'						=> true,
					'get_service_point_hours' 	=> true,
					'shipping_method'			=> $shipping_method,
					'token'						=> Tools::getToken('bpostshm'),
				), $this->ssl));
				self::$smarty->assign('url_set_service_point', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
					'ajax'				=> true,
					'set_service_point' => true,
					'shipping_method'	=> $shipping_method,
					'token'				=> Tools::getToken('bpostshm'),
				), $this->ssl));

				$this->addJqueryPlugin('scrollTo');
				$this->setTemplate('lightbox-point-list.tpl');
				break;

			case BpostShm::SHM_PLOCKER:
				$step = (int)Tools::getValue('step', 1);
				switch ($step)
				{
					default:
					case 1:
						self::$smarty->assign('module_dir', _MODULE_DIR_.$this->module->name.'/');
						self::$smarty->assign('shipping_method', $shipping_method, true);
						self::$smarty->assign('step', 1, true);

						$delivery_address = new Address($this->context->cart->id_address_delivery, $this->context->language->id);
						// UPL
						$upl_info = Tools::jsonDecode($cart_bpost->upl_info, true);
						if (!isset($upl_info))
							$upl_info = array(
								'eml' => $this->context->customer->email,
								'mob' => !empty($delivery_address->phone_mobile) ? $delivery_address->phone_mobile : '',
								'rmz' => false,
								);

						$iso_code = $this->context->language->iso_code;
						$upl_info['lng'] = in_array($iso_code, array('fr', 'nl')) ? $iso_code : 'en';
						self::$smarty->assign('upl_info', $upl_info, true);
						//
						self::$smarty->assign('url_post_upl_unregister', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'					=> true,
							'post_upl_unregister' 	=> true,
							'shipping_method'		=> $shipping_method,
							'token'					=> Tools::getToken('bpostshm'),
						), $this->ssl));

						self::$smarty->assign('url_get_point_list', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'content_only'		=> true,
							'shipping_method'	=> $shipping_method,
							'step'				=> 2,
							'token'				=> Tools::getToken('bpostshm'),
						), $this->ssl));

						$this->addJqueryPlugin('fancybox');
						$this->setTemplate('lightbox-at-247.tpl');
						break;

					case 2:
						self::$smarty->assign('module_dir', _MODULE_DIR_.$this->module->name.'/');
						self::$smarty->assign('shipping_method', $shipping_method, true);

						$named_fields = $service->getNearestValidServicePoint($shipping_method);
						foreach ($named_fields as $name => $field)
							self::$smarty->assign($name, $field, true);

						self::$smarty->assign('url_get_nearest_service_points', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'							=> true,
							'get_nearest_service_points' 	=> true,
							'shipping_method'				=> $shipping_method,
							'token'							=> Tools::getToken('bpostshm'),
						), $this->ssl));
						self::$smarty->assign('url_get_service_point_hours', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'						=> true,
							'get_service_point_hours' 	=> true,
							'shipping_method'			=> $shipping_method,
							'token'						=> Tools::getToken('bpostshm'),
						), $this->ssl));
						self::$smarty->assign('url_set_service_point', $this->context->link->getModuleLink('bpostshm', 'lightbox', array(
							'ajax'				=> true,
							'set_service_point' => true,
							'shipping_method'	=> $shipping_method,
							'token'				=> Tools::getToken('bpostshm'),
						), $this->ssl));

						$this->addJqueryPlugin('scrollTo');
						$this->setTemplate('lightbox-point-list.tpl');
						break;
				}
				break;
		}
	}

	private function jsonEncode($content)
	{
		header('Content-Type: application/json');
		die(Tools::jsonEncode($content));
	}

	public function setMedia()
	{
		parent::setMedia();

		$this->addCSS(__PS_BASE_URI__.'modules/'.$this->module->name.'/views/css/lightbox.css');
		$this->addCSS(__PS_BASE_URI__.'modules/'.$this->module->name.'/views/css/jquery.qtip.min.css');
		$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/views/js/bpostshm.js');
		$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/views/js/srgdebug.js');
		$this->addJS(__PS_BASE_URI__.'modules/'.$this->module->name.'/views/js/jquery.qtip.min.js');
		$this->addJS('https://maps.googleapis.com/maps/api/js?v=3.16&key='.Service::GMAPS_API_KEY.'&sensor=false&language='
			.$this->context->language->iso_code);

	}
}