<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


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
	foreach($key_value as $key => $value)
	{
		define($key, $value);
	}
}

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
function tuple_get($n=0,$apply=null)
{
	return function()use($n,$apply)
	{
		$args = func_get_args();
		$arg = $args[$n];
		return $apply ? $apply($arg) : $arg;
	};
}

/**
 * @param mixed $return
 * @param callable|null $apply
 * @return callable
 */
function return_dg( $return, $apply=null )
{
	if( null!==$apply && debug_assert_type( $return, 'array' ) )
	{
		return function()use( $apply, $return )
		{
			call_user_func_array( $apply, $return );
		};
	}
	else
	{
		return function()use( $return )
		{
			return $return;
		};
	}
}

/**
 * @return callable
 */
function identity_dg()
{
	return function()
	{
		return func_get_args();
	};
}

/**
 * @param callable|null $getter
 * @return callable
 */
function intval_dg($getter=null)
{
	if( $getter===null )
	{
		$getter = tuple_get();
	}
	return function()use($getter)
	{
		return intval( call_user_func_array( $getter, func_get_args() ) );
	};
}

/**
 * Prepare a delegate that returns results with comparison of $key parameter to $eq.
 *
 * @param int|string|array $eq
 * @return callable
 */
function key_eq_dg($eq)
{
	if( is_array($eq) )
	{
		return function($val,$key)use($eq)
		{
			return array_contains( $eq, $key );
		};
	}
	else
	{
		return function($val,$key)use($eq)
		{
			return $key === $eq;
		};
	}
}

/**
 * Prepare a delegate that returns results with comparison of $val parameter to $eq.
 *
 * @param int|string|array $eq
 * @return callable
 */
function val_eq_dg($eq)
{
	if( is_array($eq) )
	{
		return function($val)use($eq)
		{
			return array_contains( $eq, $val );
		};
	}
	else
	{
		return function($val)use($eq)
		{
			return $eq === $val;
		};
	}
}

/**
 * @param callable $callable
 * @return callable
 */
function not_dg( $callable )
{
	return function()use($callable)
	{
		$args = func_get_args();
		return ! call_user_func_array( $callable, $args );
	};
}

/**
 * @param callable,.. $callable
 * @return callable
 */
function or_dg( $callable )
{
	$functions = func_get_args();
	return function() use ( $functions )
	{
		$args = func_get_args();
		return array_some( $functions, function( $function )use( $args )
		{
			return call_user_func_array( $function, $args );
		} );
	};
}

/**
 * @param callable,.. $callable
 * @return callable
 */
function and_dg( $callable )
{
	$functions = func_get_args();
	return function() use ( $functions )
	{
		$args = func_get_args();
		return array_all( $functions, function( $function )use( $args )
		{
			return call_user_func_array( $function, $args );
		} );
	};
}

/**
 * @return callable
 */
function empty_dg()
{
	return function()
	{
		$arg = func_get_arg(0);
		return empty($arg);
	};
}

/**
 * @param array $array
 * @param bool $strict
 * @return callable
 */
function val_in_dg($array,$strict=false)
{
	return function($val)use($array,$strict)
	{
		return array_contains( $array, $val, $strict );
	};
}

/**
 * @param array $array
 * @param bool $strict
 * @return callable
 */
function key_in_dg($array,$strict=false)
{
	return function($val,$key)use($array,$strict)
	{
		return array_contains( $array, $key, $strict );
	};
}

/** Builds an ID from call location
 *
 * @param int $callstackDepth
 * @param boolean $appendLineNumber
 *
 * @return string
 */
function buildIdFromCallstack($callstackDepth = 0, $appendLineNumber = true)
{
	$callstackDepth += 2;

	$backtrace = debug_backtrace (DEBUG_BACKTRACE_IGNORE_ARGS, $callstackDepth);

	$id = array(str_replace(
		array(ATRIUM_PATH . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '.php'),
		array('', '_', ''),
		$backtrace[$callstackDepth - 2]['file']
	));

	$id[] = $backtrace[$callstackDepth - 1]['function'];

	if($appendLineNumber)
	{
		$id[] = sprintf('%03d', $backtrace[$callstackDepth - 2]['line']);
	}

	return implode($id, '_');
}

/**
 * @param callable $callable
 * @return mixed
 */
function call_chain( $callable )
{
	$args = func_get_args();
	array_unshift( $args, array() );
	return call_user_func_array( 'array_chain', $args );
}

/**
 * @param callable $callable
 * @return callable
 */
function call_chain_dg( $callable )
{
	$args = func_get_args();
	return function()use( $args )
	{
		return call_user_func_array( 'call_chain', $args );
	};
}

/**
 * @param mixed $value
 * @return callable
 */
function callablize( $value )
{
	if( !is_callable($value) )
	{
		$value = return_dg( $value );
	}
	return $value;
}

/**
 * @param mixed $value
 * @return callable
 */
function callablize_dg( $value )
{
	return function()use( $value )
	{
		return callablize( $value );
	};
}

/**
 * @param bool|callable $if
 * @param callable $callable
 * @return callable
 */
function call_if( $if, $callable )
{
	if( is_callable($if) )
	{
		return function($array)use( $if, $callable )
		{
			if( $if( $array ) )
			{
				$callable( $array );
			}
			return $array;
		};
	}
	else
	{
		if( $if )
		{
			return function($array)use( $callable )
			{
				$callable( $array );
				return $array;
			};
		}
		else
		{
			return identity_dg();
		}
	}
}

/**
 * @param callable $pre
 * @param callable $content
 * @param callable $post
 * @return mixed
 * @throws Exception
 */
function call_safe( $pre, $content, $post )
{
	debug_enforce( is_callable($pre), var_dump_human_compact($pre) );
	debug_enforce( is_callable($content), var_dump_human_compact($content) );
	debug_enforce( is_callable($post), var_dump_human_compact($post) );
	$init = $pre();
	try
	{
		$ret = $content( $init );
	}
	catch( Exception $e )
	{
		$post( $init );
		throw $e;
	}
	$post( $init );
	return $ret;
}

/**
 * @param callable $pre
 * @param callable $content
 * @param callable $post
 * @return callable
 */
function call_safe_dg( $pre, $content, $post )
{
	return function()use( $pre, $content, $post )
	{
		return call_safe( $pre, $content, $post );
	};
}

/**
 * @param callable|null $obj_getter
 * @return callable
 */
function get_class_dg( $obj_getter=null )
{
	if( $obj_getter===null )
	{
		return function( $obj )
		{
			return get_class( $obj );
		};
	}
	else
	{
		return function()use($obj_getter)
		{
			return get_class( $obj_getter(func_get_args()) );
		};
	}
}

/**
 * @param callable $a
 * @param callable $b
 * @return callable
 */
function eq_dg($a,$b)
{
	return function()use($a,$b)
	{
		$args = func_get_args();
		return call_user_func_array($a,$args)==call_user_func_array($b,$args);
	};
}

if(!function_exists('boolval'))
{
	/**
	 * @param mixed $var
	 *
	 * @return bool
	 */
	function boolval($var)
	{
		return (bool)$var;
	}
}

/**
 * @param callable|null $getter
 *
 * @return callable
 */
function boolval_dg($getter=null)
{
	if( $getter===null )
	{
		$getter = tuple_get();
	}
	return function()use($getter)
	{
		return boolval( call_user_func_array( $getter, func_get_args() ) );
	};
}