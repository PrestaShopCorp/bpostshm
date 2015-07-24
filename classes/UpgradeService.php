<?php
/**
* Version upgrade service class
*  
* @author    Serge <serge@stigmi.eu>
* @version   0.5.0
* @copyright Copyright (c), Eontech.net. All rights reserved.
* @license   BSD License
*/

if (!defined('_PS_VERSION_'))
	exit;

class UpgradeService
{
	protected static $instance = null;
	protected $module;
	protected $config_key;

	protected function __construct(Module $module)
	{
		$this->module = $module;
		$this->config_key = Tools::strtoupper($module->name).'_LAST_UPDATE_VERSION';
	}

	protected function __clone()
	{
	}

	public static function init(Module $module = null)
	{
		if (is_null($module))
			throw new Exception('Cannot initialise upgrade service');

		if (!isset(static::$instance))
			static::$instance = new static($module);

		return static::$instance;
	}

	public static function validVersionFormat($version)
	{
		return (bool)preg_match('/^\d+\.\d+\.\d+$/', (string)$version);
	}

	protected function getConfigVersion()
	{
		$config_version = (string)Configuration::get($this->config_key);
		return empty($config_version) ? '0.0.0' : $config_version;
	}

	protected function setConfigVersion($version)
	{
		$return = false;
		if (self::validVersionFormat($version) &&
			$version > $this->getConfigVersion())
			if (version_compare(_PS_VERSION_, '1.5', '>='))
				$return = Configuration::updateGlobalValue($this->config_key, $version);
			else
				$return = Configuration::updateValue($this->config_key, $version);

		return $return;
	}

	public function upgradeTo($version = '')
	{
		if (!self::validVersionFormat($version))
			return false;
			// throw new Exception('Invalid version format');

		$from_version = $this->getConfigVersion();
		if ($from_version >= $version)
			return true;

		$upgrade_methods = array();
		$cls = new ReflectionClass($this);
		$methods = $cls->getMethods(ReflectionMethod::IS_PRIVATE);
		foreach ($methods as $method)
			if (false !== strpos($method->name, 'upgrade_'))
			{
				$func_version = preg_replace(array('/^(upgrade_)/', '/\_/'), array('', '.'), $method->name);
				if ($func_version > $from_version && $func_version <= $version)
					$upgrade_methods[$func_version] = $method->name;
			}

		$return = true;
		$upgrade_version = $from_version;
		if (count($upgrade_methods))
		{
			try {
				asort($upgrade_methods);
				foreach ($upgrade_methods as $ver => $upgraded)
					if ($return = $this->$upgraded())
						$upgrade_version = $ver;
					else
						break;

			} catch (Exception $e) {
				// Log the error
				$return = false;
			}
		}
		$return = $return && $this->setConfigVersion($upgrade_version);

		return $return;
	}
	/***************************************/
	/*** DO NOT MODIFY THE ABOVE SECTION ***/
	/***************************************/


	/* (Module depentent) upgrade functions
	 * private function upgrade_X_XX_X()
	 * return bool
	 */
	private function upgrade_1_21_0()
	{
		return true;
	}

	/* 1.22.0 */
	private function upgrade_1_22_0()
	{
		$return = true;

		$configs_2remove = array(
			'BPOST_HOME_DELIVERY_ONLY',
		);
		foreach ($configs_2remove as $key)
			if (Validate::isConfigName($key))
				Configuration::deleteByName($key);

		// Translate BPOST_DELIVERY_OPTIONS_LIST
		// from {"home":"330|300","bpost":"","247":"350","intl":"540"}
		// to {"1":{"360":"1.2","470":["5.0","1.75"]},"2":{"300":"1.25"},"4":{},"9":{"540":"0.0"}}
		$sql = '
SELECT `id_configuration` as id, value
FROM `'._DB_PREFIX_.'configuration`
WHERE `name` = "BPOST_DELIVERY_OPTIONS_LIST"
		';
		if ($config_rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql))
		{
			$id_list = array();
			$sql = '
UPDATE `'._DB_PREFIX_.'configuration`
SET `value` = CASE';
			foreach ($config_rows as $row)
			{
				$sql .= '
	WHEN `id_configuration` = '.(int)$row['id'].' THEN "'.pSQL($this->getNewDeliveryOptionListFrom($row['value'])).'"';
				$id_list[] = (int)$row['id'];
			}
			$sql .= '
	ELSE `value`
	END
WHERE `id_configuration` in ('.implode(',', $id_list).')';

			$return = $return && Db::getInstance()->execute($sql);
		}

		return $return;
	}

	private function getNewDeliveryOptionListFrom($old_opt_string = '')
	{
		if (empty($old_opt_string))
			return false;
		elseif (false === strpos($old_opt_string, 'home'))
			return $old_opt_string;

		$mod = $this->module;
		$old2new_tbl = array(
			'home' 	=> $mod::SHM_HOME,
			'bpost' => $mod::SHM_PPOINT,
			'247' 	=> $mod::SHM_PLOCKER,
			'intl' 	=> $mod::SHM_INTL,
			);
		$delivery_options = Tools::jsonDecode($old_opt_string, true);
		foreach ($delivery_options as $dm => $opt_string)
		{
			$keys = explode('|', $opt_string);
			$new_opt = array();
			foreach ($keys as $opt_key)
				if (!empty($opt_key))
					$new_opt[$opt_key] = '0.0';

			$delivery_options[$old2new_tbl[$dm]] = $new_opt;
			unset($delivery_options[$dm]);
		}
		
		// Tools version is inadequate so don't ask!
		// return Tools::jsonEncode($delivery_options, JSON_FORCE_OBJECT);
		return json_encode($delivery_options, JSON_FORCE_OBJECT);
	}

	/* 1.30.0 */
	private function upgrade_1_30_0()
	{
		return true;
	}
}
