<?php

use ifr\main\debug as debug;

function autoload()
{
	/**
	 * @param $class_name
	 *
	 * @throws Exception
	 */
	spl_autoload_register(function($class_name)
	{
		$filename = str_replace('_','/',$class_name) . '.php';

		foreach(explode(PATH_SEPARATOR,ini_get('include_path')) as $path)
		{
			if(file_exists($path.'/'.$filename))
			{
				$found = $path.'/'.$filename;
				include $found;
				break;
			}
		}
	});
}

/**
 * @param object $obj
 * @param bool $interface
 *
 * @return bool
 */
function is_iterable($obj,$interface=false)
{
	return
		is_object($obj) ?
			$interface ?
				array_search('Iterator',class_implements($obj))!==false
				:
				true
			:
			is_array($obj)
	;
}

/**
 * @return bool
 */
function isCLI()
{
	return (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']));
}

/**
 * @return string
 */
function now()
{
	return date('Y-m-d H:i:s');
}

/**
 * @param number $price
 * @param number $discount
 *
 * @return float
 */
function discount($price,$discount)
{
	return round($price*((100-$discount)/100),2);
}

/**
 * @param number $price
 *
 * @return float
 */
function format_price($price)
{
	return round($price,2);
}

/**
 * @param string $filename
 * @param mixed $data
 */
function saveSerial($filename,$data)
{
	$filename = ROOT_PATH.'/tmp/'.$filename.'.phpserial';
	file_put_contents($filename,serialize($data));
}

/**
 * defines set of constants in key => value pairs
 * @param $key_value
 */
function define_array($key_value)
{
	debug\assert(is_array($key_value), $key_value);

	foreach($key_value as $key => $value)
	{
		$defined = defined($key);
		debug\assert(!$defined, array($key,$value));
		if ( !$defined )
		{
			define($key, $value);
		}
	}
}

/**
 * @return string
 */
function getCurrentEnv()
{
	if(defined('APPLICATION_ENV'))
	{
		return APPLICATION_ENV;
	}
	if(debug\debugHostAllow())
	{
		return 'development';
	}
	else
	{
		return 'production';
	}
}