<?php
/**
 * 2014 Stigmi
 *
 * @author    Srg@Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

require_once('../../../../../config/config.inc.php');
require_once(_PS_MODULE_DIR_.'bpostshm/backward_compatibility/Context.php');
require_once(_PS_MODULE_DIR_.'bpostshm/bpostshm.php');
require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');

class ConfigHelper extends FrontController
{
	//protected $id_lang;

	public function process()
	{
		$token = Tools::getValue('token');
		
		if ($token != Tools::getAdminToken('bpostshm'))
		{
			$this->errors[] = Tools::displayError('You do not have permission to view this.');
			return;
		}
		
		parent::process();
		
		// Looking for AJAX requests
		if (Tools::getValue('get_available_countries'))
		{
			$context = Context::getContext();
			$service = Service::getInstance($context);
			$available_countries = $service->getProductCountries();
			$this->jsonEncode($available_countries);
		}
	}

	public function displayHeader()
	{
		if (!Tools::getValue('ajax', false))
			echo '
				<script src="'._MODULE_DIR_.'bpostshm/views/js/bpostshm.js" type="text/javascript"></script>
				<script src="'._PS_JS_DIR_.'jquery/jquery-1.4.4.min.js" type="text/javascript"></script>
				<script src="'._PS_JS_DIR_.'jquery/jquery.fancybox-1.3.4.js" type="text/javascript"></script>
				<script src="https://maps.googleapis.com/maps/api/js?v=3.16&key=AIzaSyAa4S8Br_5of6Jb_Gjv1WLldkobgExB2KY&sensor=false&language=fr"'
				.'type="text/javascript"></script>
				<link href="'._THEME_CSS_DIR_.'global.css" type="text/css" rel="stylesheet" />
				<link href="'.Tools::getShopDomainSsl(true, true).'/'._MODULE_DIR_.'bpostshm/views/css/lightbox.css" type="text/css" rel="stylesheet" />
				<link href="'._PS_CSS_DIR_.'jquery.fancybox-1.3.4.css" type="text/css" rel="stylesheet" />';
	}

	/**
	 * @param mixed $content
	 */
	private function jsonEncode($content)
	{
		header('Content-Type: application/json');
		die(Tools::jsonEncode($content));
	}
}

$controller = new ConfigHelper();
$controller->init();
$controller->preProcess();
$controller->displayHeader();
$controller->process();
$controller->displayContent();