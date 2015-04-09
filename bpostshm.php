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

if (!defined('_PS_VERSION_'))
	exit;

require_once(_PS_MODULE_DIR_.'bpostshm/classes/Service.php');

class BpostShm extends CarrierModule
{
	/**
	 * 1: Post Office
	 * 2: Post Point
	 * 3: (1+2, Post Office + Post Point)
	 * 4: bpack 24/7
	 * 7: (1+2+4, Post Office + Post Point + bpack 24/7)
	 */
	const SHIPPING_METHOD_AT_HOME = 1;
	const SHIPPING_METHOD_AT_SHOP = 2;
	const SHIPPING_METHOD_AT_24_7 = 4;
	/*
	 * Need to represent sudo shipping method
	 * 9: (1+8)
	 */
	const SHIPPING_METHOD_AT_INTL = 9;

	public $carriers = array();
	public $shipping_methods = array();

	private $api_url = 'https://shippingmanager.bpost.be/ShmFrontEnd/start';

	public function __construct()
	{
		$this->author = 'Stigmi.eu';
		$this->bootstrap = true;
		$this->name = 'bpostshm';
		$this->need_instance = 0;
		$this->tab = 'shipping_logistics';
		$this->version = '1.21.0';

		$this->displayName = $this->l('bpost Shipping Manager - bpost customers only');
		$this->description = $this->l('IMPORTANT: bpostshm module description');

		parent::__construct();

		$this->hooks = array(
			'backOfficeHeader',			// displayBackOfficeHeader
			'beforeCarrier',			// displayBeforeCarrier
			'extraCarrier',				// displayCarrierList
			'paymentTop',				// displayPaymentTop
			'processCarrier',			// actionCarrierProcess
			'newOrder',					// actionValidateOrder
			'postUpdateOrderStatus',	// actionOrderStatusPostUpdate
			'updateCarrier',			// actionCarrierUpdate
			// v1.21
			// 'adminOrder',				// displayAdminOrder
			'orderDetailDisplayed',		// displayOrderDetail
			);

		$this->shipping_methods = array(
			self::SHIPPING_METHOD_AT_HOME => array(
				'name' 	=> 'Home delivery / Livraison à domicile / Thuislevering',
				'delay' => array(
					'en' =>	'Receive your parcel at home or at the office.',
					'fr' =>	'Recevez votre colis à domicile ou au bureau.',
					'nl' =>	'Ontvang uw pakket thuis of op kantoor.',
					),
				'slug' 	=> '@home',
				'lname'	=> $this->l('Home delivery'),
			),
			self::SHIPPING_METHOD_AT_SHOP => array(
				'name' 	=> 'Pick-up point / Point d’enlèvement / Afhaalpunt',
				'delay' => array(
					'en' =>	'Over 1.250 locations nearby home or the office.',
					'fr' =>	'Plus de 1250 points de livraison près de chez vous !',
					'nl' =>	'Meer dan 1250 locaties dichtbij huis of kantoor.',
					),
				'slug' 	=> '@bpost',
				'lname'	=> $this->l('Pick-up point'),
			),
			self::SHIPPING_METHOD_AT_24_7 => array(
				'name' 	=> 'Parcel locker / Distributeur de paquets / Pakjesautomaat',
				'delay' => array(
					'en' =>	'Pick-up your parcel whenever you want, thanks to the 24/7 service of bpost.',
					'fr' =>	'Retirez votre colis quand vous le souhaitez grâce au distributeur de paquets.',
					'nl' =>	'Haal uw pakket op wanneer u maar wilt, dankzij de pakjesautomaten van bpost.',
					),
				'slug' 	=> '@24/7',
				'lname'	=> $this->l('Parcel locker'),
			),
		);

		$this->_all_delivery_options = array(
			300 => array(
				'title' => $this->l('Signature'),
				'info' => $this->l('The delivery happens against signature by the receiver.'),
				),
			330 => array(
				'title' => $this->l('2nd Presentation'),
				'info' => $this->l('IMPORTANT: 2nd presentation info'),
				),
			350 => array(
				'title' => $this->l('Insurance'),
				'info' => $this->l('Insurance to insure your goods to a maximum of 500,00 euro.'),
				),
			540 => array(
				'title' => $this->l('Insurance basic'),
				'info' => $this->l('Insurance to insure your goods to a maximum of 500,00 euro.'),
				),
		);

		/** Backward compatibility */
		require_once(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
	}

	/**
	 * @return bool
	 */
	public function install()
	{
		if (!extension_loaded('curl'))
		{
			$this->_errors[] = $this->l('This module requires CURL to work properly');
			return false;
		}
		$return = true;

		$return = $return && parent::install();
		$return = $return && $this->addReplaceCarriers();

		foreach ($this->hooks as $hook)
			if (!$this->isRegisteredInHook($hook))
				$return = $return && $this->registerHook($hook);

		$return = $return && Service::updateGlobalValue('BPOST_ACCOUNT_API_URL', $this->api_url);
		$return = $return && $this->addReplaceOrderState();

		// addCartBpostTable
		$table_cart_bpost_create = array(
			'name' => _DB_PREFIX_.'cart_bpost',
			'primary_key' => 'id_cart_bpost',
			'fields' => array(
				'id_cart_bpost' => 'int(11) unsigned NOT NULL AUTO_INCREMENT',
				'id_cart' => 'int(10) unsigned NOT NULL',
				'service_point_id' => 'INT(10) unsigned NOT NULL DEFAULT 0',
				'sp_type' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'option_kmi' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'bpack247_customer' => 'TEXT',
				'date_add' => 'datetime NOT NULL',
				'date_upd' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
			)
		);
		$return = $return && $this->dbCreateTable($table_cart_bpost_create);

		// addOrderBpostTable
		$table_order_bpost_create = array(
			'name' => _DB_PREFIX_.'order_bpost',
			'primary_key' => 'id_order_bpost',
			'fields' => array(
				'id_order_bpost' => 'int(11) unsigned NOT NULL AUTO_INCREMENT',
				'reference' => 'varchar(50) NOT NULL',
				'id_shop_group' => 'int(11) unsigned NOT NULL DEFAULT 1',
				'id_shop' => 'int(11) unsigned NOT NULL DEFAULT 1',
				'current_state' => 'int(10) unsigned NOT NULL DEFAULT 0',
				'status' => 'varchar(20)',
				'shm' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'delivery_method' => 'varchar(25) NOT NULL',
				'recipient' => 'varchar(255) NOT NULL',
				'date_add' => 'datetime NOT NULL',
				'date_upd' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
			)
		);
		$return = $return && $this->dbCreateTable($table_order_bpost_create);

		// addOrderBpostLabelTable
		$table_order_bpost_label_create = array(
			'name' => _DB_PREFIX_.'order_bpost_label',
			'primary_key' => 'id_order_bpost_label',
			'fields' => array(
				'id_order_bpost_label' => 'int(11) unsigned NOT NULL AUTO_INCREMENT',
				'id_order_bpost' => 'int(11) unsigned NOT NULL',
				'is_retour' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'has_retour' => 'TINYINT(1) unsigned NOT NULL DEFAULT 0',
				'status' => 'varchar(20) NOT NULL',
				'barcode' => 'varchar(25) DEFAULT NULL',
				'barcode_retour' => 'varchar(25) DEFAULT NULL',
				'date_add' => 'datetime NOT NULL',
				'date_upd' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
			)
		);
		$return = $return && $this->dbCreateTable($table_order_bpost_label_create);

		if ((bool)Configuration::get('BPOST_USE_PS_LABELS'))
			$this->installModuleTab(
				'AdminOrdersBpost',
				// $this->l('Bpost Orders'),
				'bpost',
				Service::isPrestashop15plus() ? Tab::getIdFromClassName('AdminParentOrders') : Tab::getIdFromClassName('AdminOrders')
			);

		return $return;
	}

	/**
	 * @return bool
	 */
	public function uninstall()
	{
		// Do not include into function return because tab might already be uninstalled
		$this->uninstallModuleTab('AdminOrdersBpost');

		$return = true;

		$cache_dir = defined('_PS_CACHE_DIR_') ? _PS_CACHE_DIR_ : _PS_ROOT_DIR_.'/cache/';
		if (Tools::file_exists_cache($cache_dir.'class_bpost_index.php'))
			$return = $return && unlink($cache_dir.'class_bpost_index.php');

		foreach ($this->hooks as $hook)
			if ($this->isRegisteredInHook($hook))
				$return = $return && $this->unregisterHook($hook);

		$return = $return && $this->removeCarriers();

		$return = $return && parent::uninstall();

		return $return;
	}

	/**
	 * [addReplaceCarriers reverses carrier removal (carrier->deleted = true) if found]
	 * Create new otherwise
	 * [Remark: No need to remove carrier image icons]
	 * @author Serge <serge@stigmi.eu>
	 * @return bool true if success
	 */
	private function addReplaceCarriers()
	{
		$return = true;

		$user_groups_tmp = Group::getGroups($this->context->language->id);
		if (is_array($user_groups_tmp) && !empty($user_groups_tmp))
		{
			$user_groups = array();
			foreach ($user_groups_tmp as $group)
				$user_groups[] = (int)$group['id_group'];
		}

		$stored_carrier_ids = $this->getIdCarriers();
		foreach ($this->shipping_methods as $shipping_method => $lang_fields)
		{
			// testing int 0 for null
			$id_carrier = (int)$stored_carrier_ids[$shipping_method];
			$carrier = new Carrier($id_carrier);
			// Validate::isLoadedObject is no good for us. new object has no id!
			// if (!Validate::isLoadedObject($carrier))
			// 	return false;

			$carrier->deleted = (int)false;
			// $carrier->active = true;
			$carrier->active = BpostShm::SHIPPING_METHOD_AT_HOME == $shipping_method ||
							!Configuration::get('BPOST_HOME_DELIVERY_ONLY');

			$carrier->external_module_name = $this->name;
			$carrier->name = (string)$lang_fields['name'];
			$carrier->delay = $this->getTranslatedFields($lang_fields['delay']);
			$carrier->need_range = true;
			$carrier->shipping_external = true;
			$carrier->shipping_handling = false;
			$carrier->is_module = version_compare(_PS_VERSION_, '1.4', '<') ? 0 : 1;

			if ($ret_tmp = $carrier->save())
			{
				$id_zone_be = false;
				$zone_labels = array('België', 'Belgie', 'Belgique', 'Belgium');
				foreach ($zone_labels as $zone_label)
					if ($id_zone = Zone::getIdByName($zone_label))
					{
						$id_zone_be = (int)$id_zone;
						break;
					}

				if (!$id_zone_be)
				{
					$zone = new Zone();
					$zone->name = 'Belgium';
					$zone->active = true;
					$zone->save();
					$id_zone_be = (int)$zone->id;
				}

				Service::updateGlobalValue('BPOST_ID_ZONE_BELGIUM', (int)$id_zone_be);

				if ($id_country = Country::getByIso('BE'))
				{
					$country = new Country($id_country);
					if ((int)$country->id_zone != (int)$id_zone_be)
					{
						$country->id_zone = (int)$id_zone_be;
						$country->save();
					}

					if (!$carrier->getZone((int)$id_zone))
						$carrier->addZone((int)$id_zone);

				}

				Service::updateGlobalValue('BPOST_SHIP_METHOD_'.$shipping_method.'_ID_CARRIER', (int)$carrier->id);

				// Enable carrier for every user groups
				if (is_array($user_groups) && !empty($user_groups) && method_exists($carrier, 'setGroups'))
					$carrier->setGroups($user_groups);

				// Copy carrier logo
				$this->setIcon(_PS_MODULE_DIR_.$this->name.'/views/img/logo-carrier.jpg', _PS_SHIP_IMG_DIR_.(int)$carrier->id.'.jpg');
				$this->setIcon(
					_PS_MODULE_DIR_.$this->name.'/views/img/logo-carrier.jpg',
					_PS_TMP_IMG_DIR_.'carrier_mini_'.(int)$carrier->id.'_'.$this->context->language->id.'.jpg'
				);
			}

			$return = $return && $ret_tmp;
		}

		// Sort carriers by position rather than price
		if ($return && false !== Configuration::get('PS_CARRIER_DEFAULT_SORT'))
			Configuration::updateValue('PS_CARRIER_DEFAULT_SORT', Carrier::SORT_BY_POSITION);

		return $return;
	}

	/**
	 * [deleteCarriers mark carriers for deletion (carrier->deleted = true) if found]
	 * [Remark: No need to remove carrier image icons]
	 * @author Serge <serge@stigmi.eu>
	 * @return bool true if success
	 */
	private function removeCarriers()
	{
		$return = true;
		foreach ($this->getIdCarriers() as $id_carrier)
		{
			$carrier = new Carrier($id_carrier);
			if ($valid_carrier = Validate::isLoadedObject($carrier))
			{
				$carrier->active = false;
				$carrier->deleted = (int)true;
				$valid_carrier = $carrier->save();
			}
			$return = $return && $valid_carrier;
		}
		$return = $return && (method_exists('Carrier', 'cleanPositions') ? Carrier::cleanPositions() : true);

		return $return;
	}

	/**
	 * [addReplaceOrderState add or Replace Edit BPost 'Treated' order status to Prestashop]
	 * @author Serge <serge@stigmi.eu>
	 */
	private function addReplaceOrderState()
	{
		$return = true;
		$treated_names = array(
				'en' => 'Treated',
				'fr' => 'Traitée',
				'nl' => 'Behandeld',
			);

		$id_order_state = null;
		$order_states = OrderState::getOrderStates($this->context->language->id);
		if (!is_array($order_states))
			return false;

		foreach ($order_states as $order_state)
			if (in_array($order_state['name'], array_values($treated_names)))
			{
				// testing int 0 for null
				$id_order_state = (int)$order_state['id_order_state'];
				break;
			}

		// Creates new OrderState if id still null (ie. not found)
		$order_state = new OrderState($id_order_state);
		$order_state->name = $this->getTranslatedFields($treated_names);

		$order_state->color = '#ddff88';
		$order_state->hidden = true;
		$order_state->logable = true;
		$order_state->paid = true;

		$return = $return && $order_state->save();
		$return = $return && Service::updateGlobalValue('BPOST_ORDER_STATE_TREATED', (int)$order_state->id);

		$this->setIcon(_PS_MODULE_DIR_.$this->name.'/views/img/icons/box_closed.png', _PS_IMG_DIR_.'os/'.(int)$order_state->id.'.gif');

		return $return;
	}

	/**
	 * [getTranslatedFields helper for Prestashop mixed id_lang -> string db fields]
	 * @author Serge <serge@stigmi.eu>
	 * @param  mixed $source array of 'iso_code' => 'translated string'
	 * @return mixed         array of 'id_land' => 'translated string'
	 */
	private function getTranslatedFields($source)
	{
		$translated = array();
		if (is_array($source) && count($source) && $default = reset($source))
			if ($languages = Language::getLanguages(false))
				foreach ($languages as $language)
					if (isset($source[$language['iso_code']]))
						$translated[$language['id_lang']] = $source[$language['iso_code']];
					else
						$translated[$language['id_lang']] = $default;
			else
				$translated[$this->context->language->id] = $default;

		return $translated;
	}

	/**
	 * [dbCreateTable Create new prestashop custom named table with field attribs (Alter to match if already exists]
	 * @author Serge <serge@stigmi.eu>
	 * @param  mixed $table array of name, position, field attribs
	 * @return bool        true if no db error
	 */
	private function dbCreateTable($table)
	{
		if (!isset($table['name']) || empty($table['fields']))
			return false;

		$sql = 'CREATE TABLE IF NOT EXISTS `'.$table['name'].'` ('.PHP_EOL;
		foreach ($table['fields'] as $key => $value)
			$sql .= '`'.$key.'` '.$value.','.PHP_EOL;

		if (isset($table['primary_key']))
			$sql .= 'PRIMARY KEY (`'.$table['primary_key'].'`)';
		else
			// remove final ',' -1 if no EOL char added
			$sql = Tools::substr($sql, 0, -2);

		$sql .= ' );';

		$db = Db::getInstance(_PS_USE_SQL_SLAVE_);
		$db->execute($sql);
		$return = (0 === $db->getNumberError());

		// Check all fields are present
		$table['after'] = 'FIRST';
		return $return && $this->dbAlterTable($table);
	}

	/**
	 * [dbAlterTable Alter prestashop named table to alter/append field values at column position]
	 * @author Serge <serge@stigmi.eu>
	 * @param  mixed $table array of name, position, field attribs
	 * @return bool        true if no db error
	 */
	private function dbAlterTable($table)
	{
		if (!isset($table['name']) || empty($table['fields']))
			return false;

		// check if number of columns match
		$columns = array_keys($table['fields']);
		$columns_list = implode('\',\'', $columns);
		$sql = '
SELECT
	column_name
FROM
	information_schema.columns
WHERE
	table_schema = "'._DB_NAME_.'"
AND
	table_name = "'.$table['name'].'"
AND
	column_name in (\''.$columns_list.'\')';

		$db = Db::getInstance(_PS_USE_SQL_SLAVE_);
		// query columns already present
		$columns_present = $db->ExecuteS($sql);
		$return = (0 === $db->getNumberError());

		if ($return && count($columns_present) !== count($columns))
		{
			// required columns are Not all present
			// create missing columns
			$columns_present = array_map('current', $columns_present);
			$after = !isset($table['after']) ? '' : ('FIRST' === $table['after'] ? 'FIRST' : 'AFTER '.$table['after']);
			$sql = 'ALTER TABLE `'.$table['name'].'`'.PHP_EOL;
			foreach ($table['fields'] as $key => $value)
			{
				if (!in_array($key, $columns_present))
					$sql .= 'ADD COLUMN `'.$key.'` '.$value.' '.$after.','.PHP_EOL;

				$after = 'AFTER `'.$key.'`';
			}
			// remove final ',' -1 if no EOL char added
			$sql = Tools::substr($sql, 0, -2);

			// add missing columns
			$db->execute($sql);
			$return = (0 === $db->getNumberError());
		}

		return $return;
	}

	/**
	 * @param string $tab_class
	 * @param string $tab_name
	 * @param int $id_tab_parent
	 * @return mixed
	 */
	private function installModuleTab($tab_class, $tab_name, $id_tab_parent)
	{
		if (!Tab::getIdFromClassName($tab_class))
		{
			$tab = new Tab();
			if ($languages = Language::getLanguages(true, $this->context->shop->id))
				foreach ($languages as $language)
					$tab->name[$language['id_lang']] = $tab_name;
			else
				$tab->name = $tab_name;
			$tab->class_name = $tab_class;
			$tab->id_parent = $id_tab_parent;
			$tab->module = $this->name;
			return $tab->save();
		}
	}

	/**
	 * @param string $tab_class
	 * @return bool
	 */
	private function uninstallModuleTab($tab_class)
	{
		$return = true;
		if ($id_tab = (int)Tab::getIdFromClassName($tab_class))
		{
			$tab = new Tab($id_tab);
			$return = $return && Validate::isLoadedObject($tab) && $tab->delete();
		}
		return $return;
	}

	/**
	 * [setIcon copy src image to destination if necessary. replace if different]
	 * @author Serge <serge@stigmi.eu>
	 * @param string $src  Source path
	 * @param string $dest destination path
	 * @return  bool true if icon in place
	 */
	private function setIcon($src, $dest)
	{
		$icon_exists = file_exists($dest) && md5_file($src) === md5_file($dest);
		if (!$icon_exists)
			$icon_exists = Service::isPrestashop155plus() ? Tools::copy($src, $dest) : copy($src, $dest);

		return $icon_exists;
	}

	private function smartyAssignVersion()
	{
		$this->context->smarty->assign('version', (Service::isPrestashop16plus() ? 1.6 : (Service::isPrestashop15plus() ? 1.5 : 1.4)), true);
	}

	private function getCartBpost($id_cart = 0)
	{
		$cart_bpost = false;
		if ($id_cart)
		{
			// Service instance is required for Autoload to function
			// correctly in the Admin context ?!
			Service::getInstance($this->context);
			$cart_bpost = PsCartBpost::getByPsCartID((int)$id_cart);
		}

		return $cart_bpost;
	}

	private function isBpostShmCarrier($id_carrier)
	{
		return (bool)in_array((int)$id_carrier, $this->getIdCarriers());
	}

	private function getOrderBpost($id_order = 0)
	{
		$order_bpost = false;
		if ($id_order)
		{
			Service::getInstance($this->context);
			$order_bpost = PsOrderBpost::getByPsOrderID((int)$id_order);
		}

		return $order_bpost;
	}

	private function postValidation()
	{
		$errors = array();

		// Check PrestaShop contact info, they will be used as shipper address
		if (!Configuration::get('PS_SHOP_ADDR1')
				|| !Configuration::get('PS_SHOP_CITY')
				|| !Configuration::get('PS_SHOP_EMAIL')
				|| !Configuration::get('PS_SHOP_COUNTRY_ID')
				|| !Configuration::get('PS_SHOP_NAME')
				//|| !Configuration::get('PS_SHOP_PHONE')
				|| !Configuration::get('PS_SHOP_CODE'))
		{
			$translate = 'Do not forget to fill in shipping address ';
				if (!Service::isPrestashop15plus())
					$translate .= 'into Preferences > Contact Information';
				else
					$translate .= 'into Preferences > Store Contacts > Contact Details';
			$translate .= '!';
			$errors[] = $this->l($translate);
		}

		$id_account = Tools::getValue(
			'account_id_account',
			Configuration::get('BPOST_ACCOUNT_ID')
		);
		$passphrase = Tools::getValue(
			'account_passphrase',
			Configuration::get('BPOST_ACCOUNT_PASSPHRASE')
		);
		$api_url = Tools::getValue(
			'account_api_url',
			Configuration::get('BPOST_ACCOUNT_API_URL')
		);
		//
		$display_home_delivery_only = Tools::getValue(
			'display_home_delivery_only',
			Configuration::get('BPOST_HOME_DELIVERY_ONLY')
		);
		$display_international_delivery = Tools::getValue(
			'display_international_delivery',
			Configuration::get('BPOST_INTERNATIONAL_DELIVERY')
		);
		//
		$delivery_options_list = Tools::getValue(
			'delivery_options_list',
			Configuration::get('BPOST_DELIVERY_OPTIONS_LIST')
		);
		$label_use_ps_labels = Tools::getValue(
			'label_use_ps_labels',
			Configuration::get('BPOST_USE_PS_LABELS')
		);
		$label_pdf_format = Tools::getValue(
			'label_pdf_format',
			Configuration::get('BPOST_LABEL_PDF_FORMAT')
		);
		$auto_retour_label = Tools::getValue(
			'auto_retour_label',
			Configuration::get('BPOST_AUTO_RETOUR_LABEL')
		);
		$label_tt_integration = Tools::getValue(
			'label_tt_integration',
			Configuration::get('BPOST_LABEL_TT_INTEGRATION')
		);
		/*
		$label_tt_frequency = Tools::getValue(
			'label_tt_frequency',
			Configuration::get('BPOST_LABEL_TT_FREQUENCY')
		);

		$label_tt_update_on_open = Tools::getValue(
			'label_tt_update_on_open', 0
		);
		*/
		if (Tools::isSubmit('submitAccountSettings'))
		{
			if ((Configuration::get('BPOST_ACCOUNT_ID') !== $id_account && is_numeric($id_account)) || empty($id_account))
				Configuration::updateValue('BPOST_ACCOUNT_ID', (int)$id_account);
			if (Configuration::get('BPOST_ACCOUNT_PASSPHRASE') !== $passphrase)
				Configuration::updateValue('BPOST_ACCOUNT_PASSPHRASE', $passphrase);
			if (Configuration::get('BPOST_ACCOUNT_API_URL') !== $api_url)
			{
				if (empty($api_url))
				{
					$errors[] = $this->l('API URL shall not be empty !');
					$api_url = $this->api_url;
				}
				Service::updateGlobalValue('BPOST_ACCOUNT_API_URL', $api_url);
			}
		}
		elseif (Tools::isSubmit('submitDeliverySettings'))
		{
			if (Configuration::get('BPOST_HOME_DELIVERY_ONLY') !== $display_home_delivery_only
					&& is_numeric($display_home_delivery_only))
			{
				Service::updateGlobalValue('BPOST_HOME_DELIVERY_ONLY', (int)$display_home_delivery_only);

				foreach ($this->getIdCarriers() as $shipping_method => $id_carrier)
				{
					if (BpostShm::SHIPPING_METHOD_AT_HOME == $shipping_method)
						continue;

					$carrier = new Carrier((int)$id_carrier);
					if (Validate::isLoadedObject($carrier) && !empty($carrier->id))
					{
						$carrier->active = !$display_home_delivery_only;
						$carrier->update();
					}
				}
			}
			// Display international delivery
			if (Configuration::get('BPOST_INTERNATIONAL_DELIVERY') !== $display_international_delivery
					&& is_numeric($display_international_delivery))
				Configuration::updateValue('BPOST_INTERNATIONAL_DELIVERY', (int)$display_international_delivery);

		}
		elseif (Tools::isSubmit('submitDeliveryOptions'))
		{
			if (Configuration::get('BPOST_DELIVERY_OPTIONS_LIST') !== $delivery_options_list)
				Configuration::updateValue('BPOST_DELIVERY_OPTIONS_LIST', $delivery_options_list);

		}
		elseif (Tools::isSubmit('submitLabelSettings'))
		{
			if (Configuration::get('BPOST_USE_PS_LABELS') !== $label_use_ps_labels && is_numeric($label_use_ps_labels))
				Configuration::updateValue('BPOST_USE_PS_LABELS', (int)$label_use_ps_labels);

			if ($label_use_ps_labels)
			{
				if (Configuration::get('BPOST_LABEL_PDF_FORMAT') !== $label_pdf_format)
					Configuration::updateValue('BPOST_LABEL_PDF_FORMAT', $label_pdf_format);
				/*
				if (Configuration::get('BPOST_LABEL_TT_FREQUENCY') !== $label_tt_frequency && is_numeric($label_tt_frequency))
					Configuration::updateValue('BPOST_LABEL_TT_FREQUENCY', (int)$label_tt_frequency);
				*/
				$this->installModuleTab(
					'AdminOrdersBpost',
					// $this->l('Bpost Orders'),
					'bpost',
					Service::isPrestashop15plus() ? Tab::getIdFromClassName('AdminParentOrders') : Tab::getIdFromClassName('AdminOrders')
				);
			}
			else
			{
				$auto_retour_label = false;
				$label_tt_integration = false;
				// $label_tt_update_on_open = false;

				$this->uninstallModuleTab('AdminOrdersBpost');
			}

			if (Configuration::get('BPOST_AUTO_RETOUR_LABEL') !== $auto_retour_label && is_numeric($auto_retour_label))
				Configuration::updateValue('BPOST_AUTO_RETOUR_LABEL', (int)$auto_retour_label);
			if (Configuration::get('BPOST_LABEL_TT_INTEGRATION') !== $label_tt_integration && is_numeric($label_tt_integration))
				Configuration::updateValue('BPOST_LABEL_TT_INTEGRATION', (int)$label_tt_integration);
			// if (Configuration::get('BPOST_LABEL_TT_UPDATE_ON_OPEN') !== $label_tt_update_on_open
			// 		&& is_numeric($label_tt_update_on_open))
			// 	Configuration::updateValue('BPOST_LABEL_TT_UPDATE_ON_OPEN', (int)$label_tt_update_on_open);
		}

		$this->smarty->assign('account_id_account', $id_account, true);
		$this->smarty->assign('account_passphrase', $passphrase, true);
		$this->smarty->assign('account_api_url', $api_url, true);
		$this->smarty->assign('display_home_delivery_only', $display_home_delivery_only, true);
		$this->smarty->assign('display_international_delivery', $display_international_delivery, true);
		// delivery options
		if (isset($delivery_options_list))
			$delivery_options_list = Tools::jsonDecode($delivery_options_list, true);

		$delivery_options = array(
			'home' => array(
				'title' => '@home: Belgium',
				//'full' => '300|330|350|470',
				'full' => '330|350|470|300',
				),
			'bpost' => array(
				'title' => 'bpack@bpost: Belgium',
				'full' => '350|470',
				),
			'247' => array(
				'title' => 'Parcel locker: Belgium',
				'full' => '350|470',
				),
			'intl' => array(
				'title' => '@home: International',
				'full' => '540',
				),
			);
		foreach ($delivery_options as $name => $options)
		{
			// true gets [title & info] for each option
			$options['full'] = $this->getDeliveryOptions($options['full'], true);
			$options['list'] = isset($delivery_options_list) ? explode('|', $delivery_options_list[$name]) : array();
			$delivery_options[$name] = $options;
		}
		$this->smarty->assign('delivery_options', $delivery_options, true);
		//
		// disbling country settings
		$country_international_orders = false;
		//
		$this->smarty->assign('country_international_orders', $country_international_orders, true);
		$enabled_countries = array();
		$service = Service::getInstance($this->context);
		$product_countries = $service->getProductCountries();
		if (isset($product_countries['Error']))
			$errors[] = $this->l($product_countries['Error']);

		$this->smarty->assign('product_countries', $product_countries, true);
		$this->smarty->assign('enabled_countries', $enabled_countries, true);

		$this->smarty->assign('label_use_ps_labels', $label_use_ps_labels, true);
		$this->smarty->assign('label_pdf_format', $label_pdf_format, true);
		$this->smarty->assign('auto_retour_label', $auto_retour_label, true);
		$this->smarty->assign('label_tt_integration', $label_tt_integration, true);
		// $this->smarty->assign('label_tt_frequency', (int)$label_tt_frequency, true);
		// $this->smarty->assign('label_tt_update_on_open', $label_tt_update_on_open, true);

		$this->smarty->assign('errors', $errors, true);
		$this->smarty->assign('url_get_available_countries', $service->getControllerLink('bpostshm', 'servicepoint', array(
			'ajax'						=> true,
			'get_available_countries'	=> true,
			'token'						=> Tools::getAdminToken('bpostshm'),
		)));
	}

	/**
	 * @return mixed
	 */
	public function getContent()
	{
		$this->postValidation();
		$this->smarty->assign('version', (Service::isPrestashop16plus() ? 1.6 : (Service::isPrestashop15plus() ? 1.5 : 1.4)), true);

		return $this->display(__FILE__, 'views/templates/admin/settings.tpl', null, null);
	}

	public function getDeliveryOptions($selection = '', $inc_info = false)
	{
		if (empty($selection))
			return $this->_all_delivery_options;

		$options = array();
		$selection = explode('|', $selection);
		foreach ($selection as $key)
			if (isset($this->_all_delivery_options[$key]))
				$options[$key] = $inc_info ? $this->_all_delivery_options[$key] : $this->_all_delivery_options[$key]['title'];

		return $options;
	}

	/**
	 * [getIdCarriers get bpost shipping method -> carrier ids if they exist ]
	 * @author Serge <serge@stigmi.eu>
	 * @return mixed array of 'bpost shipping method' => 'current id_carrier' (null if missing)
	 */
	public function getIdCarriers()
	{
		if (empty($this->carriers))
			foreach (array_keys($this->shipping_methods) as $shipping_method)
				$this->carriers[$shipping_method] = ($id_carrier = (int)Configuration::get('BPOST_SHIP_METHOD_'.$shipping_method.'_ID_CARRIER'))
					? $id_carrier : null;

		return $this->carriers;
	}

	public function getShmFromCarrierID($carrier_id = 0)
	{
		$return = false;
		foreach ($this->getIdCarriers() as $shm => $id_carrier)
			if ($id_carrier == $carrier_id)
			{
				$return = $shm;
				break;
			}

		return $return;
	}

	/**
	 * The new hookAllStars team
	 * see install / uninstall for listing
	 * @author Serge <serge@stigmi.eu>
	 * @param array $params
	 */
	public function hookUpdateCarrier($params)
	{
		if (!empty($params['id_carrier']))
		{
			if ($shipping_method = array_search((int)$params['id_carrier'], $this->getIdCarriers()))
				Service::updateGlobalValue('BPOST_SHIP_METHOD_'.$shipping_method.'_ID_CARRIER', (int)$params['carrier']->id);
		}
	}

	/**
	 * @param $params
	 * @return bool
	 */
	public function hookBeforeCarrier($params)
	{
		//$cart = !empty($this->context->cart) ? $this->context->cart : $params['cart'];
		// not for 1.4
		if (!Service::isPrestashop15plus() || !isset($params['delivery_option_list']))
			return;

		$at_home_id = (int)Configuration::get('BPOST_SHIP_METHOD_'.BpostShm::SHIPPING_METHOD_AT_HOME.'_ID_CARRIER');
		$delivery_option_list = $params['delivery_option_list'];
		if ($id_address_delivery = (int)key($delivery_option_list))
		{
			$carrier_list = $delivery_option_list[$id_address_delivery];
			foreach ($carrier_list as $id_carrier => $carrier_options)
			{
				$carrier = $carrier_options['carrier_list'][(int)$id_carrier]['instance'];
				if ($at_home_id == $id_carrier)
				{
					$delivery_address = new Address((int)$id_address_delivery);
					if (Validate::isLoadedObject($delivery_address))
						$delay = $delivery_address->address1
							.(!empty($delivery_address->address2) ? ' '.$delivery_address->address2 : '')
							.', '.$delivery_address->postcode.' '.$delivery_address->city;

					$carrier->delay[$this->context->language->id] = $delay;
				}
				$name_parts = explode('/', $carrier->name);
				if (count($name_parts))
					$carrier->name = $this->l(trim($name_parts[0]));

			}
		}

		return '';
	}

	/**
	 * @param $params
	 * @return bool
	 */
	public function hookExtraCarrier($params)
	{
		$cart = !empty($this->context->cart) ? $this->context->cart : $params['cart'];
		$id_zone_delivery = $params['address']->getZoneById((int)$cart->id_address_delivery);
		$carriers = $this->getIdCarriers();
		$our_carriers = false;
		// foreach ($carriers as $shm => $id_carrier_bpost)
		foreach ($carriers as $id_carrier_bpost)
			if ($our_carriers = $cart->isCarrierInRange($id_carrier_bpost, $id_zone_delivery))
				break;

		if (!$our_carriers)
			return;

		unset($carriers[self::SHIPPING_METHOD_AT_HOME]);

		$this->smarty->assign('id_carrier', $cart->id_carrier, true);
		$this->smarty->assign('shipping_methods', $carriers, true);

		$cart_bpost = $this->getCartBpost((int)$cart->id);
		if (!empty($cart_bpost->service_point_id))
			$this->smarty->assign('service_point_id', (int)$cart_bpost->service_point_id, true);

		$url_params = array(
			'content_only'		=> true,
			'shipping_method' 	=> '',
			'token'				=> Tools::getToken('bpostshm'),
		);

		if (!empty($cart_bpost->bpack247_customer))
			$url_params['step'] = 2;

		$this->smarty->assign('version', (Service::isPrestashop16plus() ? 1.6 : (Service::isPrestashop15plus() ? 1.5 : 1.4)), true);
		$this->smarty->assign('url_lightbox', (method_exists($this->context->link, 'getModuleLink')
			? $this->context->link->getModuleLink($this->name, 'lightbox', $url_params)
			:  Tools::getShopDomainSsl(true, true).'/'.Tools::substr($this->_path, 1)
			.'controllers/front/lightbox14.php?'.http_build_query($url_params)
		), true);

		return $this->display(__FILE__, 'views/templates/hook/extra-carrier.tpl', null, null);
	}

	/**
	 *
	 * @param array $params
	 * @return bool|string
	 */
	public function hookPaymentTop($params)
	{
		return $this->hookProcessCarrier($params);
	}

	/**
	 *
	 * @param array $params
	 * @return bool|string
	 */
	public function hookProcessCarrier($params)
	{
		if (!$this->context->cart->update())
			return false;

		$cart = !empty($this->context->cart) ? $this->context->cart : $params['cart'];
		if ($shm = $this->getShmFromCarrierID($cart->id_carrier))
		{
			$cart_bpost = $this->getCartBpost((int)$cart->id);
			if (!$cart_bpost->validServicePointForSHM((int)$shm))
			{
				if (!$this->context->cookie->logged)
					$warning = '<p class="warning">'.Tools::displayError('Please sign in to see payment methods.').'</p>';
				elseif (!$this->context->cookie->checkedTOS && Configuration::get('PS_CONDITIONS'))
					$warning = '<p class="warning">'.Tools::displayError('Please accept the Terms of Service.').'</p>';
				else
					$warning = '<p class="warning">'.$this->l('Please configure selected bpost shipping method.').'</p>';

				$return = array(
					'HOOK_TOP_PAYMENT' => '',
					'HOOK_PAYMENT' => $warning,
					'summary' => $cart->getSummaryDetails(),
				);

				if (Service::isPrestashop15plus())
				{
					$return['HOOK_SHOPPING_CART'] = Hook::exec('displayShoppingCartFooter', $return['summary']);
					$return['HOOK_SHOPPING_CART_EXTRA'] = Hook::exec('displayShoppingCart', $return['summary']);

					$return['carrier_data'] = $this->getCarrierList();
				}
				else
				{
					$wrapping_fees = (float)Configuration::get('PS_GIFT_WRAPPING_PRICE');
					$wrapping_fees_tax = new Tax((int)Configuration::get('PS_GIFT_WRAPPING_TAX'));
					$wrapping_fees_tax_inc = $wrapping_fees * (1 + (((float)$wrapping_fees_tax->rate / 100)));

					$return['order_opc_adress'] = $this->context->smarty->fetch(_PS_THEME_DIR_.'order-address.tpl');
					$return['carrier_list'] = $this->getCarrierList14($params);
					$return['no_address'] = 0;
					$return['gift_price'] = Tools::displayPrice(Tools::convertPrice(Product::getTaxCalculationMethod() == 1 ?
						$wrapping_fees :
						$wrapping_fees_tax_inc,
						new Currency((int)$params['cookie']->id_currency))
					);

				}

				if (Tools::getValue('ajax', false))// && Service::isPrestashop15plus())
					die(Tools::jsonEncode($return));

				return $warning;
			}
		}

		return false;
	}

	protected function getCarrierList14($params)
	{
		$cart = $params['cart'];
		$cookie = $params['cookie'];

		$address_delivery = new Address($cart->id_address_delivery);
		if ($cookie->id_customer)
		{
			$customer = new Customer((int)$cookie->id_customer);
			$groups = $customer->getGroups();
		}
		else
			$groups = array(1);
		if (!Address::isCountryActiveById((int)$cart->id_address_delivery))
			$this->errors[] = Tools::displayError('This address is not in a valid area.');
		elseif (!Validate::isLoadedObject($address_delivery) || $address_delivery->deleted)
			$this->errors[] = Tools::displayError('This address is invalid.');
		else
		{
			$carriers = Carrier::getCarriersForOrder((int)Address::getZoneById((int)$address_delivery->id), $groups);
			$result = array(
				'checked' => $cart->id_carrier, //$this->_setDefaultCarrierSelection($carriers),
				'carriers' => $carriers,
				'HOOK_BEFORECARRIER' => Module::hookExec('beforeCarrier', array('carriers' => $carriers)),
				'HOOK_EXTRACARRIER' => Module::hookExec('extraCarrier', array('address' => $address_delivery))
			);
			return $result;
		}
		if (count($this->errors))
			return array(
				'hasError' => true,
				'errors' => $this->errors
			);
	}

	private function getCarrierList()
	{
		$address_delivery = new Address($this->context->cart->id_address_delivery);

		$cms = new CMS(Configuration::get('PS_CONDITIONS_CMS_ID'), $this->context->language->id);
		$link_conditions = $this->context->link->getCMSLink($cms, $cms->link_rewrite);
		if (!strpos($link_conditions, '?'))
			$link_conditions .= '?content_only=1';
		else
			$link_conditions .= '&content_only=1';

		$carriers = $this->context->cart->simulateCarriersOutput();
		$delivery_option = $this->context->cart->getDeliveryOption(null, false, false);

		$wrapping_fees_tax_inc = $wrapping_fees = $this->context->cart->getGiftWrappingPrice();
		$old_message = Message::getMessageByCartId((int)$this->context->cart->id);

		$free_shipping = false;
		foreach ($this->context->cart->getCartRules() as $rule)
		{
			if ($rule['free_shipping'] && !$rule['carrier_restriction'])
			{
				$free_shipping = true;
				break;
			}
		}

		$this->context->smarty->assign('isVirtualCart', $this->context->cart->isVirtualCart());

		$vars = array(
			'free_shipping' => $free_shipping,
			'checkedTOS' => (int)$this->context->cookie->checkedTOS,
			'recyclablePackAllowed' => (int)Configuration::get('PS_RECYCLABLE_PACK'),
			'giftAllowed' => (int)Configuration::get('PS_GIFT_WRAPPING'),
			'cms_id' => (int)Configuration::get('PS_CONDITIONS_CMS_ID'),
			'conditions' => (int)Configuration::get('PS_CONDITIONS'),
			'link_conditions' => $link_conditions,
			'recyclable' => (int)$this->context->cart->recyclable,
			'gift_wrapping_price' => (float)$wrapping_fees,
			'total_wrapping_cost' => Tools::convertPrice($wrapping_fees_tax_inc, $this->context->currency),
			'total_wrapping_tax_exc_cost' => Tools::convertPrice($wrapping_fees, $this->context->currency),
			'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
			'carriers' => $carriers,
			'checked' => $this->context->cart->simulateCarrierSelectedOutput(),
			'delivery_option' => $delivery_option,
			'address_collection' => $this->context->cart->getAddressCollection(),
			'opc' => (bool)Configuration::get('PS_ORDER_PROCESS_TYPE'),
			'oldMessage' => isset($old_message['message'])? $old_message['message'] : '',
			'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
				'carriers' => $carriers,
				'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
				'delivery_option' => $delivery_option
			))
		);

		Cart::addExtraCarriers($vars);

		$this->context->smarty->assign($vars);

		if (!Address::isCountryActiveById((int)$this->context->cart->id_address_delivery) && $this->context->cart->id_address_delivery != 0)
			$this->errors[] = Tools::displayError('This address is not in a valid area.');
		elseif ((!Validate::isLoadedObject($address_delivery) || $address_delivery->deleted) && $this->context->cart->id_address_delivery != 0)
			$this->errors[] = Tools::displayError('This address is invalid.');
		else
		{
			$result = array(
				'HOOK_BEFORECARRIER' => Hook::exec('displayBeforeCarrier', array(
					'carriers' => $carriers,
					'delivery_option_list' => $this->context->cart->getDeliveryOptionList(),
					'delivery_option' => $this->context->cart->getDeliveryOption(null, true)
				)),
				'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl')
			);

			Cart::addExtraCarriers($result);
			return $result;
		}
		if (count($this->errors))
			return array(
				'hasError' => true,
				'errors' => $this->errors,
				'carrier_block' => $this->context->smarty->fetch(_PS_THEME_DIR_.'order-carrier.tpl')
			);
	}

	/**
	 * @param array $params
	 */
	public function hookNewOrder($params)
	{
		$ps_order = $params['order'];
		if (!Validate::isLoadedObject($ps_order) || !$this->isBpostShmCarrier((int)$ps_order->id_carrier))
			return;

		$service = Service::getInstance($this->context);
		$service->prepareBpostOrder((int)$ps_order->id);

		if ($service_point = $this->getCartServicePointDetails((int)$ps_order->id_cart))
		{
			// Send mail
			$id_lang = (int)$ps_order->id_lang;
			$template = 'new_order';
			$subject = $this->l('New order').' - '.sprintf('%06d', $ps_order->id);

			$shop_name = Service::getBpostring(Configuration::get('PS_SHOP_NAME'));
			$customer = new Customer((int)$ps_order->id_customer);
			$customer_name = $customer->firstname.' '.$customer->lastname;
			$tpl_vars = array(
				'{customer_name}' => $customer_name,
				'{shop_name}' => $shop_name,
				'{sp_name}' => $service_point['lname'],
				'{sp_id}' => $service_point['id'],
				'{sp_office}' => $service_point['office'],
				'{sp_street}' => $service_point['street'],
				'{sp_nr}' => $service_point['nr'],
				'{sp_zip}' => $service_point['zip'],
				'{sp_city}' => $service_point['city'],
			);

			$iso_code = $this->context->language->iso_code;
			$iso_code = in_array($iso_code, array('de', 'fr', 'nl', 'en')) ? $iso_code : 'en';
			$mail_dir = _PS_MODULE_DIR_.$this->name.'/mails/';
			try {
				if (file_exists($mail_dir.$iso_code.'/'.$template.'.txt') && file_exists($mail_dir.$iso_code.'/'.$template.'.html'))
					Mail::Send($id_lang, $template, $subject, $tpl_vars,
						$customer->email,
						$customer_name,
						Configuration::get('PS_SHOP_EMAIL'),
						$shop_name,
						null, null, $mail_dir);

			} catch (Exception $e) {
				Service::logError('hookNewOrder: sending mail', $e->getMessage(), $e->getCode(), 'Order', (int)$ps_order->id);

			}
		}
	}

	/**
	 *
	 * @param array $params
	 */
	public function hookPostUpdateOrderStatus($params)
	{
		$return = false;
		if ($order_bpost = $this->getOrderBpost((int)$params['id_order']))
		{
			$order_bpost->current_state = (int)$params['newOrderStatus']->id;
			$return = $order_bpost->save();
		}

		return $return;
	}

	public function hookAdminOrder($params)
	{
		$id_order = (int)$params['id_order'];
		if ($order_bpost = $this->getOrderBpost($id_order))
		{
			if (Service::isPrestashop15plus())
				$id_cart = (int)$params['cart']->id;
			else
			{
				$ps_order = new Order($id_order);
				$id_cart = (int)$ps_order->id_cart;
			}

			$cart_bpost = $this->getCartBpost($id_cart);
			if (Validate::isLoadedObject($cart_bpost))
			{
				$bpost = array(
					'id_order' => $id_order,
					'shm' => (int)$order_bpost->shm,
					'dm' => $order_bpost->delivery_method,
					'ref' => $order_bpost->reference,
					);

				if ($sp_type = (int)$cart_bpost->sp_type)
				{
					$service = Service::getInstance();
					$service_point = $service->getServicePointDetails((int)$cart_bpost->service_point_id, $sp_type);
					$bpost['sp'] = $service_point;
				}

				$this->context->smarty->assign('module_dir', __PS_BASE_URI__.'/modules/'.$this->name);
				$this->context->smarty->assign('bpost', $bpost);

				return $this->context->smarty->fetch(dirname(__FILE__).'/views/templates/admin/admin_order_details.tpl');
			}
		}

		return 'not ours';
	}

	public function hookOrderDetailDisplayed($params)
	{
		if ($service_point = $this->getCartServicePointDetails((int)$params['order']->id_cart))
		{
			$this->smartyAssignVersion();
			$this->context->smarty->assign('module_dir', __PS_BASE_URI__.'/modules/'.$this->name);
			$this->context->smarty->assign('sp', $service_point);

			return $this->context->smarty->fetch(dirname(__FILE__).'/views/templates/front/order_details.tpl');
		}
	}

	/**
	 *  PrestaShop 1.4 hook
	 */
	public function hookBackOfficeHeader()
	{
		return '<link href="'.($this->_path).'views/css/admin-bpost.css" type="text/css" rel="stylesheet" />';
	}

	/**
	 * Undocumented Admin function
	 * @author Serge <serge@stigmi.eu>
	 * @param int $id_cart
	 */
	public function displayInfoByCart($id_cart)
	{
		if ($service_point = $this->getCartServicePointDetails($id_cart))
		{
			$this->context->smarty->assign('module_dir', __PS_BASE_URI__.'/modules/'.$this->name);
			$this->context->smarty->assign('sp', $service_point);

			return $this->context->smarty->fetch(dirname(__FILE__).'/views/templates/admin/admin_order_details.tpl');
		}
	}

	protected function getCartServicePointDetails($id_cart)
	{
		$service_point = false;
		$cart_bpost = $this->getCartBpost((int)$id_cart);
		if (Validate::isLoadedObject($cart_bpost))
			if ($sp_type = (int)$cart_bpost->sp_type)
			{
				$service = Service::getInstance();
				$service_point = $service->getServicePointDetails((int)$cart_bpost->service_point_id, $sp_type);
				// we're done with sp_type so..
				if (1 === $sp_type) $sp_type = 2;
				$service_point['lname'] = $this->shipping_methods[$sp_type]['lname'];
				$service_point['slug'] = $this->shipping_methods[$sp_type]['slug'];
			}

		return $service_point;
	}

	/**
	 * @param array $params
	 * @param $shipping_cost
	 */
	public function getOrderShippingCost($params, $shipping_cost)
	{
		if (isset($params))
			return $shipping_cost;

		return false;
	}

	/**
	 * @param array $params
	 */
	public function getOrderShippingCostExternal($params)
	{
		return $this->getOrderShippingCost($params, 0);
	}
}