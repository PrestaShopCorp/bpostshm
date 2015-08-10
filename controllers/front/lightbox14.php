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

		// Building display page
		self::$smarty->assign('version', (Service::isPrestashop16plus() ? 1.6 : (Service::isPrestashop15plus() ? 1.5 : 1.4)), true);
		switch ($shipping_method)
		{
			case BpostShm::SHM_PPOINT:
				self::$smarty->assign('module_dir', _MODULE_DIR_.'bpostshm/');
				self::$smarty->assign('shipping_method', $shipping_method, true);

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
						self::$smarty->assign('url_post_upl_unregister', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
							.http_build_query(array(
								'ajax'					=> true,
								'post_upl_unregister' 	=> true,
								'shipping_method'		=> $shipping_method,
								'token'					=> Tools::getToken('bpostshm'),
							)));

						self::$smarty->assign('url_get_point_list', _MODULE_DIR_.'bpostshm/'._CONTROLLER_FILE_.'?'
							.http_build_query(array(
								'content_only'		=> true,
								'shipping_method'	=> $shipping_method,
								'step'				=> 2,
								'token'				=> Tools::getToken('bpostshm'),
							)));

						$this->setTemplate('lightbox-at-247.tpl');
						break;

					case 2:
						self::$smarty->assign('module_dir', _MODULE_DIR_.'bpostshm/');
						self::$smarty->assign('shipping_method', $shipping_method, true);

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

