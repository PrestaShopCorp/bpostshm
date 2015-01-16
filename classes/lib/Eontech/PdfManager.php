<?php
/**
* pdf file manager class Prestashop module helper
*  
* @author    Serge <serge@stigmi.eu>
* @version   0.5.0
* @copyright Copyright (c), Eontech.net. All rights reserved.
* @license   BSD License
*/

if (!defined('_PS_VERSION_'))
	exit;

class EontechPdfManager extends EontechBaseObject
{
	const ERR_INITIALIZE = 3;
	const ERR_ACCESS = 4;

	private $_links = array();

	public function __construct($module_name = '', $pdf_dir = 'pdf', $raise_exceptions = false)
	{
		parent::__construct(true, $raise_exceptions);

		$pdf_dir = $this->prependSeparator($pdf_dir);
		if (!is_dir(_PS_MODULE_DIR_.$module_name))
			$this->setError('Cannot locate module '.$module_name.' directory');
		elseif ($this->_active_dir = $this->getPath(_PS_MODULE_DIR_.$module_name.$pdf_dir))
			$this->_active_url = _PS_BASE_URL_._MODULE_DIR_.$module_name.$pdf_dir;
		else
			$this->setError('Cannot create pdf folder.');

	}

	public function setActiveFolder($sub_path)
	{
		$sub_path = $this->prependSeparator($sub_path);
		if ($this->_active_dir = $this->getPath($this->_active_dir, $sub_path))
			$this->_active_url = $this->_active_url.$sub_path;
		else
			$this->setError('Cannot create pdf folder '.$sub_path);

		$this->_links = array();
		$pdf_files = glob($this->_active_dir.'/*.pdf');
		foreach($pdf_files as $file)
			$this->_links[] = $this->_active_url.$this->prependSeparator(basename($file));

	}

	public function writePdf($bytes)
	{
		if ($this->hasError() || !isset($bytes))
			return false;

		// filename is the next index
		$file_name = (string)($this->count() + 1).'.pdf';
		$file_path = $this->_active_dir.DIRECTORY_SEPARATOR.$file_name;

		if ($fp = fopen($file_path, 'w'))
		{
			fwrite($fp, $bytes);
			fclose($fp);
			$this->_links[] = $this->_active_url.DIRECTORY_SEPARATOR.$file_name;
		}
		else
			$this->setError('Error opening pdf file for writing', self::ERR_ACCESS);
		
	}

	protected function prependSeparator($dir)
	{
		return  DIRECTORY_SEPARATOR == Tools::substr($dir, 0, 1) ? $dir : DIRECTORY_SEPARATOR.$dir;
	}

	protected function getPath($base_dir, $sub_dirs = '')
	{
		// ex. getPath('module/bpost/pdf', 'today/ref123')
		$path = empty($sub_dirs) ? $base_dir : $base_dir.$sub_dirs;
		if (is_writable($path))
			return $path;

		$path = $base_dir;
		$sub_dirs = explode('/', $sub_dirs);
		foreach ($sub_dirs as $sub_dir)
		{
			$path .= empty($sub_dir) ? '' : DIRECTORY_SEPARATOR.$sub_dir;
			if (!is_writable($path))
				mkdir($path, 0755);

		}

		if (is_writable($path))
			return $path;
		else
		{
			$this->setError('Path: '.$path.' is not accessible.', self::ERR_ACCESS);
			return false;
		}
	}

	public function count()
	{
		return count($this->_links);
	}

	public function hasPrints()
	{
		return (bool)$this->count();
	}

	public function getLinks()
	{
		return  $this->_links;
	}

	protected function setError($msg, $severity = self::ERR_INITIALIZE)
	{
		parent::setError($msg, $severity);
	}
}