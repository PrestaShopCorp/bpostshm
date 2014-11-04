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

	public $carriers = array();
	public $shipping_methods = array();

	private $api_url = 'https://shippingmanager.bpost.be/ShmFrontEnd/start'; /* 'http://shippingmanager.bpost.be/shippingmanager/frontEnd' */

	public function __construct()
	{
		$this->author = 'Stigmi.eu';
		$this->bootstrap = true;
		$this->name = 'bpostshm';
		$this->need_instance = 0;
		$this->tab = 'shipping_logistics';
		$this->version = '1.0';

		parent::__construct();

		$this->displayName = $this->l('bpost Shipping Manager - bpost customers only');
		$this->description = $this->l('IMPORTANT: bpostshm module description');

		$this->shipping_methods = array(
			self::SHIPPING_METHOD_AT_HOME => array(
				'name' 	=> $this->l('Delivery at home or at the office'),
				'delay' => $this->l('Receive your parcel at home or at the office.'),
				'slug' 	=> '@home',
			),
			self::SHIPPING_METHOD_AT_SHOP => array(
				'name' 	=> $this->l('Pick-up in a PostOffice or Post Point'),
				'delay' => $this->l('Over 1.400 locations nearby home or the office.'),
				'slug' 	=> '@bpost',
			),
			self::SHIPPING_METHOD_AT_24_7 => array(
				'name' 	=> $this->l('Delivery in a parcel locker'),
				'delay' => $this->l('Pick-up your parcel whenever you want, thanks to the 24/7 service of bpost.'),
				'slug' 	=> '@24/7',
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
			/*
			470 => array(
				'title' => $this->l('Saturday Delivery'),
				'info' => $this->l(''),
				),
			*/
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
			exit;

		$return = true;

		if (!Service::isPrestashopFresherThan14())
		{
			$return = $return && copy(_PS_MODULE_DIR_.$this->name.'/1.4/override/classes/Carrier.php', _PS_ROOT_DIR_.'/override/classes/Carrier.php');
			$return = $return && copy(_PS_MODULE_DIR_.$this->name.'/1.4/override/classes/Cart.php', _PS_ROOT_DIR_.'/override/classes/Cart.php');
			$return = $return && copy(
				_PS_MODULE_DIR_.$this->name.'/1.4/override/controllers/ParentOrderController.php',
				_PS_ROOT_DIR_.'/override/controllers/ParentOrderController.php'
			);
		}

		$cache_dir = defined('_PS_CACHE_DIR_') ? _PS_CACHE_DIR_ : _PS_ROOT_DIR_.'/cache/';
		if (file_exists($cache_dir.'class_index.php'))
			$return = $return && (bool)unlink($cache_dir.'class_index.php');

		$return = $return && parent::install();
		$return = $return && $this->addCarriers();

		if (Service::isPrestashopFresherThan14())
		{
			$return = $return && $this->registerHook('actionTopPayment');
			$return = $return && $this->registerHook('actionValidateOrder');
			$return = $return && $this->registerHook('displayBackOfficeHeader');
			$return = $return && $this->registerHook('actionCarrierProcess'); // OPC
		}
		else
		{
			$return = $return && $this->registerHook('paymentTop');
			$return = $return && $this->registerHook('orderConfirmation');
			$return = $return && $this->registerHook('backOfficeHeader');
		}

		$return = $return && $this->registerHook('extraCarrier');
		$return = $return && $this->registerHook('updateCarrier');

		$return = $return && Configuration::updateValue(
			'BPOST_ACCOUNT_API_URL_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id),
			$this->api_url
		);

		$return = $return && $this->addOrderState();

		// Db::execute ALTER TABLE will return nor TRUE nor FALSE
		// WRONG: no point pretending if required table/columns don't actually exist
		/*
		$this->alterCartTable();
		$this->addOrderLabelTable();

		if (!Service::isPrestashopFresherThan14())
			$this->alterOrderTable();
		*/

		// alterCartTable
		$table_cart_alter = array(
			'name' => _DB_PREFIX_.'cart',
			'fields' => array(
				'bpack247_customer' => 'TEXT',
				'service_point_id' => 'INT(10) unsigned'
			)
		);
		$return = $return && $this->dbAlterTable($table_cart_alter);

		// addOrderLabelTable
		$table_order_label_create = array(
			'name' => _DB_PREFIX_.'order_label',
			'primary_key' => 'id_order_label',
			'fields' => array(
				'id_order_label' => 'int(11) NOT NULL AUTO_INCREMENT',
				'reference' => 'varchar(50) NOT NULL',
				'id_shop' => 'int(11) unsigned NOT NULL DEFAULT 1',
				'status' => 'varchar(20) NOT NULL',
				'delivery_method' => 'varchar(25) NOT NULL',
				'recipient' => 'varchar(255) NOT NULL',
				'barcode' => 'varchar(25) DEFAULT NULL',
				'date_add' => 'datetime NOT NULL',
				'date_upd' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
			)
		);
		$return = $return && $this->dbCreateTable($table_order_label_create);

		if (!Service::isPrestashopFresherThan14())
		{
			// alterOrdersTable
			$table_orders_alter = array(
				'name' => _DB_PREFIX_.'orders',
				'after' => 'id_order',
				'fields' => array(
					'reference' => 'VARCHAR(9)'
				)
			);
			$return = $return && $this->dbAlterTable($table_orders_alter);
		}

		return $return;
	}

	/**
	 * @return bool
	 */
	public function uninstall()
	{
		// Do not include into function return because tab might already be uninstalled
		$this->uninstallModuleTab('AdminBpostOrders');

		$return = true;

		$cache_dir = defined('_PS_CACHE_DIR_') ? _PS_CACHE_DIR_ : _PS_ROOT_DIR_.'/cache/';
		if (file_exists($cache_dir.'class_index.php'))
			$return = $return && unlink($cache_dir.'class_index.php');

		if (file_exists($cache_dir.'class_bpost_index.php'))
			$return = $return && unlink($cache_dir.'class_bpost_index.php');

		$return = $return && $this->unregisterHook('actionValidateOrder');
		$return = $return && $this->unregisterHook('displayBackOfficeHeader');
		$return = $return && $this->unregisterHook('extraCarrier');
		$return = $return && $this->unregisterHook('updateCarrier');
		$return = $return && $this->deleteCarriers();
		$return = $return && parent::uninstall();

		return $return;
	}

	/**
	 * @return bool
	 */
	private function addCarriers()
	{
		$return = true;

		$user_groups_tmp = Group::getGroups($this->context->language->id, $this->context->shop->id);
		if (is_array($user_groups_tmp) && !empty($user_groups_tmp))
		{
			$user_groups = array();
			foreach ($user_groups_tmp as $group)
				$user_groups[] = $group['id_group'];
		}

		foreach ($this->shipping_methods as $shipping_method => $lang_fields)
		{
			$carrier = new Carrier();
			$carrier->active = true;
			if ($languages = Language::getLanguages(true, $this->context->shop->id))
				foreach ($languages as $language)
					$carrier->delay[$language['id_lang']] = $lang_fields['delay'];
			else
				$carrier->delay[$this->context->language->id] = $lang_fields['delay'];
			$carrier->external_module_name = $this->name;
			$carrier->name = $lang_fields['name'];
			$carrier->need_range = true;
			$carrier->shipping_external = true;
			$carrier->shipping_handling = false;

			if ($ret_tmp = $carrier->save())
			{
				// Affect carrier zones
				if (in_array($shipping_method, array(
						self::SHIPPING_METHOD_AT_HOME,
						self::SHIPPING_METHOD_AT_SHOP,
						self::SHIPPING_METHOD_AT_24_7,
					)))
				{
					$id_zone_be = false;
					$zone_labels = array('België', 'Belgique', 'Belgium');
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

					Configuration::updateValue(
						'BPOST_ID_COUNTRY_BELGIUM_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id),
						(int)$id_zone_be
					);

					if ($id_country = Country::getByIso('BE'))
					{
						$country = new Country($id_country);
						if (method_exists('Country', 'affectZoneToSelection'))
						{
							if ($country->affectZoneToSelection(array($id_country), $id_zone_be))
								$carrier->addZone((int)$id_zone_be);
						}
						else
						{
							$country->id_zone = (int)$id_zone_be;
							if ($country->save())
								$carrier->addZone((int)$id_zone_be);
						}
					}
				}
				else
					if ($zones = Zone::getZones(true))
						foreach ($zones as $zone)
							$carrier->addZone((int)$zone['id_zone']);

				Configuration::updateValue(
					'BPOST_SHIP_METHOD_'.$shipping_method.'_ID_CARRIER_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id),
					(int)$carrier->id
				);

				// Enable carrier for every user groups
				if (is_array($user_groups) && !empty($user_groups) && method_exists($carrier, 'setGroups'))
					$carrier->setGroups($user_groups);

				// Copy carrier logo
				copy(_PS_MODULE_DIR_.$this->name.'/views/img/logo-carrier.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg');
				copy(
					_PS_MODULE_DIR_.$this->name.'/views/img/logo-carrier.jpg',
					_PS_TMP_IMG_DIR_.'/carrier_mini_'.(int)$carrier->id.'_'.$this->context->language->id.'.jpg'
				);
			}

			$return = $return && $ret_tmp;
		}

		// Sort carriers by position rather than price
		if ($return && false !== Configuration::get('PS_CARRIER_DEFAULT_SORT'))
			Configuration::updateValue('PS_CARRIER_DEFAULT_SORT', Carrier::SORT_BY_POSITION);

		return $return;
	}

	private function addOrderState()
	{
		$return = true;

		$exists = false;
		$order_states = OrderState::getOrderStates($this->context->language->id);
		foreach ($order_states as $order_state)
			if ('Treated' == $order_state['name'])
			{
				$exists = true;
				break;
			}

		if ($exists)
			return $return;

		$order_state = new OrderState();
		if ($languages = Language::getLanguages(true, $this->context->shop->id))
			foreach ($languages as $language)
				$order_state->name[$language['id_lang']] = $this->l('Treated');
		else
			$order_state->name[$this->context->language->id] = $this->l('Treated');
		$order_state->color = '#108510';
		$order_state->hidden = true;
		$order_state->logable = true;
		$order_state->paid = true;
		if (!$order_state->save())
			return !$return;

		$return = $return && Configuration::updateValue(
			'BPOST_ORDER_STATE_TREATED_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id),
			(int)$order_state->id
		);

		copy(_PS_MODULE_DIR_.$this->name.'/views/img/icons/box_closed.png', _PS_IMG_DIR_.'os/'.(int)$order_state->id.'.gif');

		return $return;
	}

	/**
	 * @return array
	 */
	public function getIdCarriers()
	{
		if (empty($this->carriers))
			foreach (array_keys($this->shipping_methods) as $shipping_method)
				$this->carriers[$shipping_method] = (int)Configuration::get('BPOST_SHIP_METHOD_'.$shipping_method.'_ID_CARRIER_'
					.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id));

		return $this->carriers;
	}

	/**
	 * @return bool
	 */
	private function deleteCarriers()
	{
		$return = true;
		foreach ($this->getIdCarriers() as $id_carrier)
		{
			$carrier = new Carrier((int)$id_carrier);

			if (!empty($carrier->id))
			{
				if ($ret_tmp = $carrier->delete())
				{
					unlink(_PS_SHIP_IMG_DIR_.(int)$carrier->id.'.jpg');
					unlink(_PS_TMP_IMG_DIR_.'carrier_mini_'.(int)$carrier->id.'_'.$this->context->language->id.'.jpg');
				}

				$return = $return && $ret_tmp;
			}
		}

		$return = $return && (method_exists('Carrier', 'cleanPositions') ? Carrier::cleanPositions() : true);

		return $return;
	}

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
			$sql = substr($sql, 0, -2);

		$sql .= ' );';

		$db = Db::getInstance(_PS_USE_SQL_SLAVE_);
		$db->execute($sql);
		$return = (0 === $db->getNumberError());

		// Check all fields are present
		$table['after'] = 'FIRST';
		return $return && $this->dbAlterTable($table);
	}

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
			$sql = substr($sql, 0, -2);

			// add missing columns
			$db->execute($sql);
			$return = (0 === $db->getNumberError());
		}

		return $return;
	}
/*
	private function addOrderLabelTable()
	{
		Db::getInstance(_PS_USE_SQL_SLAVE_)->execute('
CREATE TABLE IF NOT EXISTS
	`'._DB_PREFIX_.'order_label`
(
	`id_order_label` int(11) NOT NULL AUTO_INCREMENT,
	`reference` varchar(50) NOT NULL,
	`status` varchar(20) NOT NULL,
	`delivery_method` varchar(25) NOT NULL,
	`recipient` varchar(255) NOT NULL,
	`barcode` varchar(25) NOT NULL,
	`date_add` datetime NOT NULL,
	`date_upd` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id_order_label`)
)');
	}

	private function alterCartTable()
	{
		$db = Db::getInstance(_PS_USE_SQL_SLAVE_);

		if (!$db->getRow('
SELECT
	column_name
FROM
	information_schema.columns
WHERE
	table_schema = "'._DB_NAME_.'"
AND
	table_name = "'._DB_PREFIX_.'cart"
AND
	column_name = "bpack247_customer"'))
				$db->execute('
ALTER TABLE
	`'._DB_PREFIX_.'cart`
ADD COLUMN
	`bpack247_customer` TEXT,
ADD COLUMN
	`service_point_id` INT(10) unsigned');
	}

	private function alterOrderTable()
	{
		$db = Db::getInstance(_PS_USE_SQL_SLAVE_);

		if (!$db->getRow('
SELECT
	column_name
FROM
	information_schema.columns
WHERE
	table_schema = "'._DB_NAME_.'"
AND
	table_name = "'._DB_PREFIX_.'order"
AND
	column_name = "reference"'))
				$db->execute('
ALTER TABLE
	`'._DB_PREFIX_.'order`
ADD COLUMN
	`reference` VARCHAR(9)');
	}
*/
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
		if ($id_tab = Tab::getIdFromClassName($tab_class))
		{
			$tab = new Tab($id_tab);
			$return = $return && $tab->delete();
		}
		return $return;
	}

	private function postValidation()
	{
		$errors = array();
		$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);

		// Check PrestaShop contact info, they will be used as shipper address
		if (!Configuration::get('PS_SHOP_ADDR1')
				|| !Configuration::get('PS_SHOP_CITY')
				|| !Configuration::get('PS_SHOP_EMAIL')
				|| !Configuration::get('PS_SHOP_COUNTRY_ID')
				|| !Configuration::get('PS_SHOP_NAME')
				|| !Configuration::get('PS_SHOP_PHONE')
				|| !Configuration::get('PS_SHOP_CODE'))
		{
			$translate = 'Do not forget to fill in shipping address ';
				if (!Service::isPrestashopFresherThan14())
					$translate .= 'into Preferences > Contact Information';
				else
					$translate .= 'into Preferences > Store Contacts > Contact Details';
			$translate .= '!';
			$errors[] = $this->l($translate);
		}

		$id_account = Tools::getValue(
			'account_id_account',
			Configuration::get('BPOST_ACCOUNT_ID_'.$context_shop_id)
		);

		$passphrase = Tools::getValue(
			'account_passphrase',
			Configuration::get('BPOST_ACCOUNT_PASSPHRASE_'.$context_shop_id)
		);
		$api_url = Tools::getValue(
			'account_api_url',
			Configuration::get('BPOST_ACCOUNT_API_URL_'.$context_shop_id)
		);
		//
		$display_home_delivery_only = Tools::getValue(
			'display_home_delivery_only',
			Configuration::get('BPOST_HOME_DELIVERY_ONLY_'.$context_shop_id)
		);
		$display_international_delivery = Tools::getValue(
			'display_international_delivery',
			Configuration::get('BPOST_INTERNATIONAL_DELIVERY_'.$context_shop_id)
		);
		//
		$delivery_options_list = Tools::getValue(
			'delivery_options_list',
			Configuration::get('BPOST_DELIVERY_OPTIONS_LIST_'.$context_shop_id)
		);
		//
		$country_international_orders = Tools::getValue(
			'country_international_orders',
			Configuration::get('BPOST_INTERNATIONAL_ORDERS_'.$context_shop_id)
		);
		$enabled_country_list = Tools::getValue(
			'enabled_country_list',
			Configuration::get('BPOST_ENABLED_COUNTRY_LIST_'.$context_shop_id)
		);
		//
		$label_use_ps_labels = Tools::getValue(
			'label_use_ps_labels',
			Configuration::get('BPOST_USE_PS_LABELS_'.$context_shop_id)
		);
		$label_pdf_format = Tools::getValue(
			'label_pdf_format',
			Configuration::get('BPOST_LABEL_PDF_FORMAT_'.$context_shop_id)
		);
		$label_retour_label = Tools::getValue(
			'label_retour_label',
			Configuration::get('BPOST_LABEL_RETOUR_LABEL_'.$context_shop_id)
		);
		$label_tt_integration = Tools::getValue(
			'label_tt_integration',
			Configuration::get('BPOST_LABEL_TT_INTEGRATION_'.$context_shop_id)
		);
		/*
		$label_tt_frequency = Tools::getValue(
			'label_tt_frequency',
			Configuration::get('BPOST_LABEL_TT_FREQUENCY_'.$context_shop_id)
		);
		*/
		$label_tt_update_on_open = Tools::getValue(
			'label_tt_update_on_open',
			Configuration::get('BPOST_LABEL_TT_UPDATE_ON_OPEN_'.$context_shop_id)
		);

		if (Tools::isSubmit('submitAccountSettings'))
		{
			if (Configuration::get('BPOST_ACCOUNT_ID_'.$context_shop_id) !== $id_account && is_numeric($id_account))
				Configuration::updateValue('BPOST_ACCOUNT_ID_'.$context_shop_id, (int)$id_account);
			if (Configuration::get('BPOST_ACCOUNT_PASSPHRASE_'.$context_shop_id) !== $passphrase)
				Configuration::updateValue('BPOST_ACCOUNT_PASSPHRASE_'.$context_shop_id, $passphrase);
			if (Configuration::get('BPOST_ACCOUNT_API_URL_'.$context_shop_id) !== $api_url)
			{
				if (empty($api_url))
				{
					$errors[] = $this->l('API URL shall not be empty !');
					$api_url = $this->api_url;
				}
				Configuration::updateValue('BPOST_ACCOUNT_API_URL_'.$context_shop_id, $api_url);
			}
		}
		elseif (Tools::isSubmit('submitDeliverySettings'))
		{
			if (Configuration::get('BPOST_HOME_DELIVERY_ONLY_'.$context_shop_id) !== $display_home_delivery_only
					&& is_numeric($display_home_delivery_only))
			{
				Configuration::updateValue('BPOST_HOME_DELIVERY_ONLY_'.$context_shop_id, (int)$display_home_delivery_only);

				foreach ($this->getIdCarriers() as $shipping_method => $id_carrier)
				{
					if (BpostShm::SHIPPING_METHOD_AT_HOME == $shipping_method)
						continue;

					$carrier = new Carrier((int)$id_carrier);

					if (!empty($carrier->id))
					{
						$carrier->active = !$display_home_delivery_only;
						$carrier->update();
					}
				}
			}
			// Display international delivery
			if (Configuration::get('BPOST_INTERNATIONAL_DELIVERY_'.$context_shop_id) !== $display_international_delivery
					&& is_numeric($display_international_delivery))
				Configuration::updateValue('BPOST_INTERNATIONAL_DELIVERY_'.$context_shop_id, (int)$display_international_delivery);

		}
		elseif (Tools::isSubmit('submitDeliveryOptions'))
		{
			if (Configuration::get('BPOST_DELIVERY_OPTIONS_LIST_'.$context_shop_id) !== $delivery_options_list)
				Configuration::updateValue('BPOST_DELIVERY_OPTIONS_LIST_'.$context_shop_id, $delivery_options_list);

		}
		elseif (Tools::isSubmit('submitCountrySettings'))
		{
			Configuration::updateValue('BPOST_INTERNATIONAL_ORDERS_'.$context_shop_id, $country_international_orders);

			if (2 == $country_international_orders)
			{
				if (is_array($enabled_country_list))
				{
					if (1 === count($enabled_country_list) && 'REMOVE' === $enabled_country_list[0])
						$enabled_country_list = '';
					else
					{
						$id_carriers = array(
							(int)Configuration::get('BPOST_SHIP_METHOD_'.self::SHIPPING_METHOD_AT_HOME.'_ID_CARRIER_'.$context_shop_id),
						);

						foreach ($enabled_country_list as $iso_code)
							if ($id_country = Country::getByIso($iso_code))
							{
								$country = new Country((int)$id_country, 1);

								if ($country_id_zone = Zone::getIdByName($country->name))
									$id_zone = (int)$country_id_zone;
								else
								{
									$zone = new Zone();
									$zone->name = $country->name;
									$zone->active = true;
									$zone->save();
									$id_zone = (int)$zone->id;
								}

								foreach ($id_carriers as $id_carrier)
								{
									$carrier = new Carrier((int)$id_carrier);
									if (method_exists('Country', 'affectZoneToSelection'))
									{
										if ($country->affectZoneToSelection(array($id_country), $id_zone))
											$carrier->addZone((int)$id_zone);
									}
									else
									{
										$country->id_zone = (int)$id_zone;
										if ($country->save())
											$carrier->addZone((int)$id_zone);
									}
								}
							}

						$enabled_country_list = implode('|', $enabled_country_list);
					}
				}

				if (Configuration::get('BPOST_ENABLED_COUNTRY_LIST_'.$context_shop_id) !== $enabled_country_list)
					Configuration::updateValue('BPOST_ENABLED_COUNTRY_LIST_'.$context_shop_id, $enabled_country_list);
			}
			else
				Configuration::updateValue('BPOST_ENABLED_COUNTRY_LIST_'.$context_shop_id, '');
		}
		elseif (Tools::isSubmit('submitLabelSettings'))
		{
			if (Configuration::get('BPOST_USE_PS_LABELS_'.$context_shop_id) !== $label_use_ps_labels && is_numeric($label_use_ps_labels))
				Configuration::updateValue('BPOST_USE_PS_LABELS_'.$context_shop_id, (int)$label_use_ps_labels);

			if ($label_use_ps_labels)
			{
				if (Configuration::get('BPOST_LABEL_PDF_FORMAT_'.$context_shop_id) !== $label_pdf_format)
					Configuration::updateValue('BPOST_LABEL_PDF_FORMAT_'.$context_shop_id, $label_pdf_format);
				/*
				if (Configuration::get('BPOST_LABEL_TT_FREQUENCY_'.$context_shop_id) !== $label_tt_frequency && is_numeric($label_tt_frequency))
					Configuration::updateValue('BPOST_LABEL_TT_FREQUENCY_'.$context_shop_id, (int)$label_tt_frequency);
				*/
				$this->installModuleTab(
					'AdminBpostOrders',
					'bpost',
					Service::isPrestashopFresherThan14() ? Tab::getIdFromClassName('AdminParentOrders') : Tab::getIdFromClassName('AdminOrders')
				);
			}
			else
			{
				$label_retour_label = false;
				$label_tt_integration = false;
				$label_tt_update_on_open = false;

				$this->uninstallModuleTab('AdminBpostOrders');
			}

			if (Configuration::get('BPOST_LABEL_RETOUR_LABEL_'.$context_shop_id) !== $label_retour_label && is_numeric($label_retour_label))
				Configuration::updateValue('BPOST_LABEL_RETOUR_LABEL_'.$context_shop_id, (int)$label_retour_label);
			if (Configuration::get('BPOST_LABEL_TT_INTEGRATION_'.$context_shop_id) !== $label_tt_integration && is_numeric($label_tt_integration))
				Configuration::updateValue('BPOST_LABEL_TT_INTEGRATION_'.$context_shop_id, (int)$label_tt_integration);
			if (Configuration::get('BPOST_LABEL_TT_UPDATE_ON_OPEN_'.$context_shop_id) !== $label_tt_update_on_open
					&& is_numeric($label_tt_update_on_open))
				Configuration::updateValue('BPOST_LABEL_TT_UPDATE_ON_OPEN_'.$context_shop_id, (int)$label_tt_update_on_open);
		}

		$this->smarty->assign('account_id_account', $id_account, true);
		$this->smarty->assign('account_passphrase', $passphrase, true);
		$this->smarty->assign('account_api_url', $api_url, true);
		$this->smarty->assign('display_home_delivery_only', $display_home_delivery_only, true);
		$this->smarty->assign('display_international_delivery', $display_international_delivery, true);
		// delivery options
		if (isset($delivery_options_list))
			$delivery_options_list = json_decode($delivery_options_list, true);

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
		$this->smarty->assign('country_international_orders', $country_international_orders, true);
		$service = Service::getInstance($this->context);
		$product_countries = $service->getProductCountries();
		if (isset($product_countries['Error']))
			$errors[] = $this->l($product_countries['Error']);
		else
		{
			$enabled_countries = $service->explodeCountryList($enabled_country_list);

			$this->smarty->assign('product_countries', $product_countries, true);
			$this->smarty->assign('enabled_countries', $enabled_countries, true);
		}

		$this->smarty->assign('label_use_ps_labels', $label_use_ps_labels, true);
		$this->smarty->assign('label_pdf_format', $label_pdf_format, true);
		$this->smarty->assign('label_retour_label', $label_retour_label, true);
		$this->smarty->assign('label_tt_integration', $label_tt_integration, true);
		// $this->smarty->assign('label_tt_frequency', (int)$label_tt_frequency, true);
		$this->smarty->assign('label_tt_update_on_open', $label_tt_update_on_open, true);

		$this->smarty->assign('errors', $errors, true);
		$this->smarty->assign('url_get_available_countries', $service->getControllerLink('bpostshm', 'servicepoint', array(
			'ajax'						=> true,
			'get_available_countries'	=> true,
			'token'						=> Tools::getAdminToken('bpostshm'),
		)));
	}

	/**
	 * OPC
	 *
	 * @param array $params
	 * @return bool|string
	 */
	public function hookActionTopPayment($params)
	{
		return $this->hookActionCarrierProcess($params);
	}

	/**
	 * OPC
	 *
	 * @param array $params
	 * @return bool|string
	 */
	public function hookActionCarrierProcess($params)
	{
		if (!$this->context->cart->update())
			return false;

		$cart = !empty($this->context->cart) ? $this->context->cart : $params['cart'];

		$carriers = $this->getIdCarriers();
		unset($carriers[self::SHIPPING_METHOD_AT_HOME]);

		if (Tools::getValue('ajax', false) && empty($params['passthrough']))
		{
			// Reset selected bpost service point
			$cart->service_point_id = 0;
			$cart->update();
		}

		if (in_array($cart->id_carrier, $carriers) && empty($cart->service_point_id))
		{
			if (!$this->context->customer->isLogged())
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

			$return['HOOK_SHOPPING_CART'] = Hook::exec('displayShoppingCartFooter', $return['summary']);
			$return['HOOK_SHOPPING_CART_EXTRA'] = Hook::exec('displayShoppingCart', $return['summary']);

			if (Service::isPrestashopFresherThan14())
				$return['carrier_data'] = $this->getCarrierList();

			if (Tools::getValue('ajax', false) && Service::isPrestashopFresherThan14())
				die(Tools::jsonEncode($return));

			return $warning;
		}

		return false;
	}

	/**
	 * @param array $params
	 */
	public function hookActionValidateOrder($params)
	{
		foreach ($this->getIdCarriers() as $shipping_method => $id_carrier)
		{
			if ((int)$params['order']->id_carrier == $id_carrier)
			{
				$service = Service::getInstance($this->context);
				$service->makeOrder((int)$params['order']->id, $shipping_method);

				// 2014-10-30:  No Retour label when placing order 1st time
				/*
				$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);

				// Generate retour if auto-generation is enabled
				if ((bool)Configuration::get('BPOST_LABEL_RETOUR_LABEL_'.$context_shop_id))
				{
					$reference = Configuration::get('BPOST_ACCOUNT_ID_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id)).'_';
					if (Service::isPrestashopFresherThan14())
						$reference .= Tools::substr($params['order']->reference, 0, 53);
					else
						$reference .= Tools::substr($params['order']->reference, 0, 42).'_'.time();
					$service->addLabel($reference, true);
				}
				*/

				break;
			}
		}
	}

	/**
	 * Used to ease order process testings.
	 * When hooked, will be call on order confirmation page when refreshing page
	 * /!\ is a duplicate of $this->hookActionValidateOrder()
	 *
	 * @param $params
	 */
	public function hookDisplayOrderConfirmation($params)
	{
		/*foreach ($this->getIdCarriers() as $shipping_method => $id_carrier)
		{
			if ((int)$params['objOrder']->id_carrier == $id_carrier)
			{
				$service = Service::getInstance($this->context);
				$service->makeOrder((int)$params['objOrder']->id, $shipping_method);

				$context_shop_id = (isset($this->context->shop) && !is_null($this->context->shop->id) ? $this->context->shop->id : 1);

				// Generate retour if auto-generation is enabled
				if ((bool)Configuration::get('BPOST_LABEL_RETOUR_LABEL_'.$context_shop_id))
				{
					$reference = Configuration::get('BPOST_ACCOUNT_ID_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id)).'_'
						.Tools::substr($params['objOrder']->reference, 0, 53);
					$service->addLabel($reference, true);
				}

				break;
			}
		}*/
	}

	public function hookDisplayBackOfficeHeader()
	{
		$this->context->controller->addCSS(($this->_path).'views/css/admin.css');
	}

	/**
	 *  PrestaShop 1.4 hook
	 */
	public function hookBackOfficeHeader()
	{
		if (!Service::isPrestashopFresherThan14())
			return '<link href="'.($this->_path).'views/css/admin.css" type="text/css" rel="stylesheet" />';
		return '';
	}

	/**
	 * @param $params
	 * @return bool
	 */
	public function hookExtraCarrier($params)
	{
		$cart = !empty($this->context->cart) ? $this->context->cart : $params['cart'];

		$carriers = $this->getIdCarriers();
		unset($carriers[self::SHIPPING_METHOD_AT_HOME]);

		$this->smarty->assign('id_carrier', $cart->id_carrier, true);
		$this->smarty->assign('shipping_methods', $carriers, true);

		if (!empty($cart->service_point_id))
			$this->smarty->assign('service_point_id', (int)$cart->service_point_id, true);

		$url_params = array(
			'content_only'		=> true,
			'shipping_method' 	=> '',
			'token'				=> Tools::getToken('bpostshm'),
		);

// Srg
		if (!empty($cart->bpack247_customer))
			$url_params['step'] = 2;
// Srg end

		$this->smarty->assign('url_lightbox', (method_exists($this->context->link, 'getModuleLink')
			? $this->context->link->getModuleLink($this->name, 'lightbox', $url_params)
			:  Tools::getShopDomainSsl(true, true).'/'.Tools::substr($this->_path, 1)
			.'1.4/controllers/front/lightbox.php?'.http_build_query($url_params)
		), true);

		$this->smarty->assign('version', (Service::isPrestashop16() ? 1.6 : (Service::isPrestashopFresherThan14() ? 1.5 : 1.4)), true);

		return $this->display(__FILE__, 'views/templates/hook/extra-carrier.tpl', null, null);
	}

	/**
	 * PrestaShop 1.4 hook
	 *
	 * @param array $order
	 */
	public function hookOrderConfirmation($order)
	{
		if (!Service::isPrestashopFresherThan14())
			return $this->hookActionValidateOrder(array(
					'cart' 		=> $this->context->cart,
					'customer' 	=> new Customer((int)$this->context->cookie->id_customer),
					'order' 	=> $order['objOrder'],
				));
		return '';
	}

	/**
	 * @param array $params
	 */
	public function hookUpdateCarrier($params)
	{
		if (!empty($params['id_carrier']))
		{
			if ($shipping_method = array_search((int)$params['id_carrier'], $this->getIdCarriers()))
				Configuration::updateValue(
					'BPOST_SHIP_METHOD_'.$shipping_method.'_ID_CARRIER_'.(is_null($this->context->shop->id) ? '1' : $this->context->shop->id),
					(int)$params['carrier']->id
				);
		}
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
	 * @return mixed
	 */
	public function getContent()
	{
		$this->postValidation();

		$this->smarty->assign('version', (Service::isPrestashop16() ? 1.6 : (Service::isPrestashopFresherThan14() ? 1.5 : 1.4)), true);

		return $this->display(__FILE__, 'views/templates/admin/settings.tpl', null, null);
	}

	/**
	 * @param array $params
	 * @param $shipping_cost
	 */
	public function getOrderShippingCost($params, $shipping_cost)
	{
		return $shipping_cost;
	}

	/**
	 * @param array $params
	 */
	public function getOrderShippingCostExternal($params)
	{

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
}