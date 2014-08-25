<?php
/**
 * 2014 Stigmi
 *
 * @author    Srg@Stigmi <www.stigmi.eu>
 * @copyright 2014 Stigmi
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit();

//require_once(_PS_MODULE_DIR_.'bpostshm/bpostshm.php');
require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');

//class AdminConfigHelperController extends ModuleAdminController
class BpostShmConfigHelperModuleFrontController extends ModuleFrontController
{

	//protected $id_lang;

	public function initContent()
	{
		
		$token = Tools::getValue('token');
		
		if (!$this->viewAccess() || $token != Tools::getAdminToken('bpostshm'))
		{
			$this->errors[] = Tools::displayError('You do not have permission to view this.');
			return;
		}
		
		parent::initContent();
		
		// Looking for AJAX requests
		if (Tools::getValue('get_available_countries'))
		{
			$service = Service::getInstance($this->context);
			$available_countries = $service->getProductCountries();
//$this->write($available_countries);
			$this->jsonEncode($available_countries);
			
		}


	}


	/**
	 * @param mixed $content
	 */
	private function jsonEncode($content)
	{
		header('Content-Type: application/json');
		die(Tools::jsonEncode($content));
	}

	private function write($content)
	{
		die('<pre>'.print_r($content, true).'</pre>');
	}
}