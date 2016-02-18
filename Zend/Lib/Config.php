<?php

namespace IFR\Main\Zend\Lib;

class Config
{

	/**
	 * @var \Zend_Config
	 */
	protected $_config;

	public function __construct($filePath)
	{
		$this->_configFile = $filePath;
		$this->_config = $this->_loadConfig($this->_configFile, true);

		if(@file_exists($this->_configFile . ".local"))
		{
			$this->_config->merge($this->_loadConfig($this->_configFile . ".local"));
		}

		$env = getCurrentEnv();

		$this->_config->merge($this->_config->{$env});

		$this->_config->env = $env;

		if(\Zend_Registry::isRegistered('config'))
		{
			$c = \Zend_Registry::get('config');
			$c->merge($this->_config);
			\Zend_Registry::set('config', $c);
		}
		else
		{
			\Zend_Registry::set('config', $this->_config);
		}
		return $this;
	}

	public function getConfig($section = null)
	{
		if($section)
		{
			return $this->_config->{$section};
		}
		return $this->_config;
	}

	protected function _loadConfig($fullpath, $write = false)
	{
		if (file_exists($fullpath))
		{
			$extArray = explode(".", trim(strtolower($fullpath)));

			do
			{
				$ext = array_pop($extArray);
			} while($ext == "local");

			switch($ext)
			{
				case 'ini':
					$cfg = new \Zend_Config_Ini($fullpath, null, $write);
					break;
				case 'xml':
					$cfg = new \Zend_Config_Xml($fullpath, null, $write);
					break;
				default:
					throw new \Zend_Config_Exception("Invalid '$ext' format for config file");
					break;
			}
		} else {
				throw new \Zend_Application_Resource_Exception("File '$fullpath' does not exist");
		}
		return $cfg;
	}

	public function toCCCP($configIn = null)
	{
		$ret = array();

		if($configIn === null)
		{
			$config = $this->_config;
		}
		else
		{
			$config = $configIn;
		}

		if(is_iterable($config))
		{
			foreach($config as $key=>$value)
			{
				if($key != 'db')
				{
					$ret[$key] = $this->toCCCP($value);
				}
			}
		}

		if($configIn === null)
		{
			$ret = \Zend_Json::encode(object($ret));
		}
		return $ret;
	}

	public function toArray()
	{
		return $this->_config->toArray();
	}

	public function merge(Config $config)
	{
		$this->_config->merge($config->getConfig());
		return $this;
	}
}

