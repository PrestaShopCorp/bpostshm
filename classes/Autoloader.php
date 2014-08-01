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

class Autoloader
{
	/**
	 * File where classes index is stored
	 */
	const INDEX_FILE = 'cache/class_bpost_index.php';

	/**
	 * @var Autoloader
	 */
	protected static $instance;

	/**
	 *  @var array array('classname' => 'path/to/override', 'classnamecore' => 'path/to/class/core')
	 */
	public $index = array();

	protected function __construct()
	{
		$this->root_dir = _PS_ROOT_DIR_.'/';
		if (file_exists($this->root_dir.self::INDEX_FILE))
			$this->index = include($this->root_dir.self::INDEX_FILE);
		else
			$this->generateIndex();
	}

	/**
	 * Get instance of autoload (singleton)
	 *
	 * @return Autoload
	 */
	public static function getInstance()
	{
		if (!Autoloader::$instance)
			Autoloader::$instance = new Autoloader();

		return Autoloader::$instance;
	}

	/**
	 * @param string $classname
	 */
	public function load($classname)
	{
		// regenerate the class index if the requested file doesn't exists
		if (empty($this->index[$classname]) || !is_file($this->index[$classname]))
			$this->generateIndex();

		if (!empty($this->index[$classname]))
			require_once($this->index[$classname]);

	}

	/**
	 * From autoload.php
	 *
	 * @param string $classname
	 */
	public function loadPS14($class_name)
	{
		if (function_exists('smartyAutoload') && smartyAutoload($class_name))
			return true;

		$class_name = str_replace(chr(0), '', $class_name);
		$class_dir = _PS_CLASS_DIR_;
		$override_dir = dirname(__FILE__).'/../override/classes/';
		$file_in_override = file_exists($override_dir.$class_name.'.php');
		$file_in_classes = file_exists($class_dir.$class_name.'.php');

		// This is a Core class and its name is the same as its declared name
		if (Tools::substr($class_name, -4) == 'Core')
			require_once($class_dir.Tools::substr($class_name, 0, -4).'.php');
		else
		{
			if ($file_in_override && $file_in_classes)
			{
				require_once($class_dir.str_replace(chr(0), '', $class_name).'.php');
				require_once($override_dir.$class_name.'.php');
			}
			elseif (!$file_in_override && $file_in_classes)
			{
				require_once($class_dir.str_replace(chr(0), '', $class_name).'.php');
				$class_infos = new ReflectionClass($class_name.((interface_exists($class_name, false) || class_exists($class_name, false)) ? '' : 'Core'));
				if (!$class_infos->isInterface() && Tools::substr($class_infos->name, -4) == 'Core')
					eval(($class_infos->isAbstract() ? 'abstract ' : '').'class '.$class_name.' extends '.$class_name.'Core {}');
			}
			elseif ($file_in_override && !$file_in_classes)
				require_once($override_dir.$class_name.'.php');
			else
			{
				$class_name = explode('\\', $class_name);
				array_splice($class_name, 0, 2);
				require_once(_PS_MODULE_DIR_.'bpostshm/classes/lib/'.implode('/', $class_name).'.php');
			}
		}
	}

	public function generateIndex()
	{
		$classes = $this->getClassesFromDir(_PS_MODULE_DIR_.'bpostshm/classes/lib/');
		if (!Service::isPrestashopFresherThan14())
		{
			$classes = array_merge($classes, $this->getClassesFromDir(_PS_CLASS_DIR_));
			$classes = array_merge($classes, $this->getClassesFromDir(_PS_TOOL_DIR_.'smarty/sysplugins/'));
		}

		ksort($classes);
		$content = '<?php return '.var_export($classes, true).'; ?>';

		// Write classes index on disc to cache it
		$filename = $this->root_dir.self::INDEX_FILE;
		$filename_tmp = tempnam(dirname($filename), basename($filename.'.'));
		if ($filename_tmp !== false && file_put_contents($filename_tmp, $content, LOCK_EX) !== false)
		{
			if (!rename($filename_tmp, $filename))
				unlink($filename_tmp);
			else
				@chmod($filename, 0666);
		}
		// $filename_tmp couldn't be written. $filename should be there anyway (even if outdated), no need to die.
		else
			error_log('Cannot write temporary file '.$filename_tmp);

		$this->index = $classes;

	}

	public function getClassesFromDir($path = '', &$classes = array())
	{
		foreach (scandir($path) as $file)
		{
			if ($file[0] != '.')
			{
				if (is_dir($path.$file))
					$this->getClassesFromDir($path.$file.'/', $classes);
				else if (Tools::substr($file, -4) == '.php')
				{
					$content = Tools::file_get_contents($path.$file);
					$pattern = '#(class|interface)\s+(?P<classname>[a-zA-Z0-9]+)(\w|\s|\\\)+\{#ix';
					if (preg_match($pattern, $content, $m) && !empty($m['classname']))
						if (strpos($m['classname'], basename($file, '.php')))
							$classes[$m['classname']] = $path.$file;
				}
			}
		}

		return $classes;
	}
}