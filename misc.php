<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)

function autoload()
{
	/**
	 * Warning: namespaces are not completely implemented
	 *
	 * @param $class_name
	 * @throws Exception
	 */
	spl_autoload_register(
		function ( $class_name )
		{
			$parts = explode( '\\', $class_name );
			$class_name = array_pop( $parts );
			$filename = str_replace( '_', DIRECTORY_SEPARATOR, $class_name ).'.php';

			if( count( $parts )>0 )
			{
				$filename = implode( DIRECTORY_SEPARATOR, $parts ) . DIRECTORY_SEPARATOR. $filename;
			}

			foreach( explode( PATH_SEPARATOR, ini_get( 'include_path' ) ) as $path )
			{
				$candidate = $path.DIRECTORY_SEPARATOR.$filename;
				if( file_exists( $candidate ) )
				{
					include $candidate;
					break;
				}
			}
		}
	);
}

/**
 * @param mixed $obj
 *
 * @return bool
 */
function is_iterable( $obj/*,$interface=false*/ )
{
	return is_array( $obj )
		|| ( is_object( $obj ) && $obj instanceof Traversable )
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
	if( debug_assert(is_array($key_value), $key_value) )
	{
		foreach($key_value as $key => $value)
		{
			if( debug_assert(!defined($key), array($key,$value)) )
			{
				define($key, $value);
			}
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
	else
	{
		return 'development';
	}
}

$tz = @date_default_timezone_get();
if( 'development' !== getCurrentEnv() )
{
	logger_log('Timezone not specified! Please set "date.timezone" in php.ini');
}
$tz = date_default_timezone_set($tz);

/**
 * @return string
 */
function now()
{
	return date('Y-m-d H:i:s');
}

/**
 * @param mixed $val
 *
 * @return mixed
 */
function to_hash($val)
{
	return (string) $val;
}

/**
 * @param int $n
 * @param callable|null $apply
 * @return callable
 */
function tuple_get($n,$apply=null)
{
	return function()use($n,$apply)
	{
		$args = func_get_args();
		$arg = $args[$n];
		return $apply ? $apply($arg) : $arg;
	};
}

/**
 * Prepare a delegate that returns results with comparision of $key parameter to $eq.
 *
 * @param int|string $eq
 * @return callable
 */
function key_eq_dg($eq)
{
	return function($val,$key)use($eq)
	{
		return $key === $eq;
	};
}
