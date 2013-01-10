<?php

namespace misc;

function autoload()
{
	//    ini_set('unserialize_callback_func','spl_autoload_call');
	//
	//    spl_autoload_register("_autoload");

	/**
	 * @param $class_name
	 *
	 * @throws Exception
	 */
	function __autoload($class_name)
	{
		$filename = str_replace('_','/',$class_name) . '.php';

		$found = false;
		foreach(explode(PATH_SEPARATOR,ini_get('include_path')) as $path)
		{
			if(file_exists($path.'/'.$filename))
			{
				$found = $path.'/'.$filename;
			}
		}

		if(!$found)
		{
			//			printr($filename);
			//			$dbg = debug_backtrace();
			//			printr($dbg);

			throw new Exception('Class '.$class_name.' not found');
		}

		require_once $found;
	}
}

/**
 * @param string $class_name
 *
 * @throws Exception
 */
function _autoload($class_name)
{
	$filename = str_replace('_','/',$class_name) . '.php';

	$found = false;
	foreach(explode(PATH_SEPARATOR,ini_get('include_path')) as $path)
	{
		if(file_exists($path.'/'.$filename))
		{
			$found = $path.'/'.$filename;
		}
	}

	if(!$found)
	{
		throw new Exception('Class '.$class_name.' not found');
	}

	require_once $found;
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