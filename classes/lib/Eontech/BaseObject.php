<?php
/**
* Base Object class
*  
* @author    Serge <serge@stigmi.eu>
* @version   0.5.0
* @copyright Copyright (c), Eontech.net. All rights reserved.
* @license   BSD License
*/

class EontechBaseObject
{
	protected $_error_code;
	protected $_errors;

	protected $_include_method;
	protected $_raise_exceptions;

	public function __construct($include_method = false, $raise_exceptions = false)
	{
		$this->resetError();

		$this->_include_method = $include_method;
		$this->_raise_exceptions = $raise_exceptions;
	}

	public function hasError()
	{
		return 0 !== $this->_error_code;
	}

	public function getErrors()
	{
		return $this->hasError() ? $this->_errors : array();
	}

	protected function resetError()
	{
		$this->_error_code = 0;
		$this->_errors = array();
	}

	protected function setError($msg, $severity = 1)
	{
		$method = '';
		if ($this->_include_method)
		{
			$trace = debug_backtrace();
			$method = '::'.$trace[1]['function']; 
		}
		$error = get_class($this).$method.' '.$msg;

		$this->_errors[] = $error;
		$this->_error_code = $severity;

		if ($this->_raise_exceptions) 
			throw new Exception($error, $severity);
	}
}