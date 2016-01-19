<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)

const ENV_PRODUCTION    = 'production';
const ENV_DEVELOPMENT   = 'development';
const ENV_TEST          = 'test';

if( !function_exists('getCurrentEnv') )
{
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
			return ENV_DEVELOPMENT;
		}
	}
}

if( !function_exists('is_iterable') )
{
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
}

if( !function_exists('is_type') )
{
	/**
	 * @param mixed $var
	 * @param string $type
	 * @return bool
	 */
	function is_type( $var, $type )
	{
		if( $type === 'callable' && is_callable($var) )
		{
			$t = 'callable';
		}
		else
		{
			$t = gettype($var);
		}
		return $t === $type;
	}
}

if( !function_exists('is_type_dg') )
{
	/**
	 * @param string|callable $type
	 * @param callable|null $var
	 * @return callable
	 * @throws Exception
	 */
	function is_type_dg($type,$var=null)
	{
		if( is_string($type) )
		{
			$type = return_dg($type);
		}
		else
		{
			debug_enforce( is_type($type,'callable'), "Invalid type identifier ".var_dump_human_compact($type) );
		}
		if( null===$var )
		{
			$var = tuple_get();
		}
		else
		{
			debug_enforce( is_type($var,'callable'), "is_type_dg expects callable returning actual variable, given: ".var_dump_human_compact($var) );
		}
		return function()use($type,$var)
		{
			$args = func_get_args();
			return is_type(
				call_user_func_array( $var, $args ),
				call_user_func_array( $type, $args )
			);
		};
	}
}

if( !function_exists('gettype_dg') )
{
	/**
	 * @param callable $value
	 * @return callable
	 */
	function gettype_dg($value)
	{
		return function()use($value)
		{
			return gettype( call_user_func_array( $value, func_get_args() ) );
		};
	}
}

if( !function_exists('discount') )
{
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
}

if( !function_exists('format_price') )
{
	/**
	 * @param number $price
	 *
	 * @return float
	 */
	function format_price($price)
	{
		return round($price,2);
	}
}

if( !function_exists('saveSerial') )
{
	/**
	 * @param string $filename
	 * @param mixed $data
	 */
	function saveSerial($filename,$data)
	{
        $filename = defined('ROOT_PATH') ? ROOT_PATH : '';
		$filename .= '/tmp/'.$filename.'.phpserial';
		file_put_contents($filename,serialize($data));
	}
}

if( !function_exists('define_array') )
{
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
}

if( !function_exists('now') )
{
	/**
	 * @return string
	 */
	function now()
	{
		return date('Y-m-d H:i:s');
	}
}

if( !function_exists('to_hash') )
{
	/**
	 * @param mixed $val
	 *
	 * @return mixed
	 */
	function to_hash($val)
	{
		return (string) $val;
	}
}

if( !function_exists('tuple_get') )
{
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
}

if( !function_exists('tuple_carry') )
{
	/**
	 * @param callable|int|null $carry_setter
	 * @param callable|null $apply
	 * @param mixed $seed
	 * @return callable
	 */
	function tuple_carry($carry_setter=null,$apply=null,$seed=null)
	{
		$carry = $seed;
		if( $carry_setter===null )
		{
			$carry_setter = tuple_get(1);
		}
		elseif( is_int( $carry_setter ) )
		{
			$carry_setter = tuple_get( $carry_setter );
		}
		else
		{
			debug_enforce_type( $carry_setter, 'callable' );
		}
		if( $apply===null )
		{
			$apply = tuple_get(0);
		}
		else
		{
			debug_enforce_type( $apply, 'callable' );
		}
		return function()use($carry_setter,$apply,&$carry)
		{
			$args = func_get_args();
			$argsWithCarry = array_prepend( $args, $carry );
			$carry = call_user_func_array( $carry_setter, $argsWithCarry );
			return call_user_func_array( $apply, $argsWithCarry );
		};
	}
}

if( !function_exists('return_dg') )
{
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
}

if( !function_exists('identity_dg') )
{
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
}

if( !function_exists('intval_dg') )
{
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
}

if( !function_exists('floatval_dg') )
{
	/**
	 * @param callable|null $getter
	 * @return callable
	 */
	function floatval_dg($getter=null)
	{
		if( $getter===null )
		{
			$getter = tuple_get();
		}
		return function()use($getter)
		{
			return floatval( call_user_func_array( $getter, func_get_args() ) );
		};
	}
}

if( !function_exists('key_eq_dg') )
{
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
}

if( !function_exists('val_eq_dg') )
{
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
}

if( !function_exists('not_dg') )
{
	/**
	 * @param callable $callable
	 * @return callable
	 */
	function not_dg( $callable )
	{
		debug_enforce( is_callable($callable), "Expected callable, got ".var_dump_human_compact($callable) );
		return function()use($callable)
		{
			$args = func_get_args();
			return ! call_user_func_array( $callable, $args );
		};
	}
}

if( !function_exists('or_dg') )
{
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
}

if( !function_exists('and_dg') )
{
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
}

if( !function_exists('empty_dg') )
{
	/**
	 * @param callable|null $subject
	 * @return callable
	 */
	function empty_dg( $subject=null )
	{
		if( null===$subject )
		{
			$subject = tuple_get(0);
		}
		else
		{
			debug_enforce_type( $subject, 'callable' );
		}
		return function()use( $subject )
		{
			$args = func_get_args();
			$subject = call_user_func_array( $subject, $args );
			return empty( $subject );
		};
	}
}

if( !function_exists('val_in_dg') )
{
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
}

if( !function_exists('key_in_dg') )
{
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
}

if( !function_exists('buildIdFromCallstack') )
{
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

		$id = [str_replace(
			[ATRIUM_PATH . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '.php'],
			['', '_', ''],
			$backtrace[$callstackDepth - 2]['file']
		)];

		$id[] = $backtrace[$callstackDepth - 1]['function'];

		if($appendLineNumber)
		{
			$id[] = sprintf('%03d', $backtrace[$callstackDepth - 2]['line']);
		}

		return implode($id, '_');
	}
}

if( !function_exists('call_chain') )
{
	/**
	 * @param callable $callable
	 * @return mixed
	 */
	function call_chain( $callable )
	{
		$args = func_get_args();
		array_unshift( $args, [] );
		return call_user_func_array( 'array_chain', $args );
	}
}

if( !function_exists('call_chain_dg') )
{
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
}

if( !function_exists('callablize') )
{
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
}

if( !function_exists('callablize_dg') )
{
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
}

if( !function_exists('call_if') )
{
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
}

if( !function_exists('if_dg') )
{
	/**
	 * @param bool|callable $constraint
	 * @param mixed|callable $true
	 * @param mixed|callable $false
	 * @return callable
	 */
	function if_dg($constraint,$true,$false)
	{
		if( is_bool($constraint) )
		{
			$constraint = return_dg($constraint);
		}
		if( !is_callable( $true ) )
		{
			$true = return_dg( $true );
		}
		if( !is_callable( $false ) )
		{
			$false = return_dg( $false );
		}
		return function()use($constraint,$true,$false)
		{
			$args = func_get_args();
			if( call_user_func_array( $constraint, $args ) )
			{
				$ret = call_user_func_array( $true, $args );
			}
			else
			{
				$ret = call_user_func_array( $false, $args );
			}
			return $ret;
		};
	}
}

if( !function_exists('match') )
{
	/**
	 * @param mixed $subject
	 * @param array,.. $option [$match($subject)=>$bool,$response($subject)=>$mixed]
	 * @return mixed
	 * @see ensure
	 */
	function match( $subject, $option )
	{
		$options = array_chain(
			func_get_args(),
			array_rest_dg(1),
			array_map_val_dg( if_dg( is_type_dg('array'), tuple_get(0), array_dg(return_dg(return_dg(true)),tuple_get(0)) ) )
		);
		foreach( $options as $pair )
		{
			list($match,$project) = $pair;
			if( is_callable($match) ? $match( $subject ) : $match===$subject )
			{
				return is_callable($project) ? $project( $subject ) : $project;
			}
		}
		debug_assert( false, 'Cannot find match for '.var_dump_human_compact($subject).' out of '.var_dump_human_compact($options) );
		return $subject;
	}
}

if( !function_exists('match_dg') )
{
	/**
	 * @param mixed|callable|null $subject
	 * @param array,.. $option [$match($subject)=>$bool,$response($subject)=>$mixed]
	 * @return mixed
	 * @see ensure_dg
	 */
	function match_dg( $subject, $option )
	{
		if( $subject===null )
		{
			$subject = tuple_get(0);
		}
		else
		{
			$subject = callablize( $subject );
		}
		$options = array_rest( func_get_args(), 1 );
		return function () use ( $subject, $options )
		{
			$args = $options;
			array_unshift( $args, call_user_func_array( $subject, func_get_args() ) );
			return call_user_func_array( 'match', $args );
		};
	}
}

if( !function_exists('switch_dg') )
{
	/**
	 * @param mixed|callable $subject
	 * @param callable|mixed $option
	 * @return callable
	 */
	function switch_dg( $subject, $option )
	{
		$subject = callablize($subject);
		$options = array_chain(
			func_get_args(),
			array_rest_dg(1),
			array_map_val_dg(
				if_dg(
					is_type_dg('array'),
					function($array){ return [eq_dg(tuple_get(0),callablize(array_get($array,0))),callablize(array_get($array,1))]; }, //FIXME
					array_dg( return_dg(return_dg(true)), tuple_get(0,'callablize') )
				)
			)
		);
		return function()use($subject,$options)
		{
			$args = func_get_args();
			$subject = call_user_func_array( $subject, $args );
			foreach( $options as $pair )
			{
				list($match,$result) = $pair;
				if( call_user_func( $match, $subject ) )
				{
					return call_user_func_array( $result, $args );
				}
			}
			debug_assert( false, 'Cannot switch from '.var_dump_human_compact($subject).' to '.var_dump_human_compact($options) );
			return $subject;
		};
	}
}

if( !function_exists('call_safe') )
{
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
}

if( !function_exists('call_safe_dg') )
{
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
}

if( !function_exists('get_class_dg') )
{
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
}

if( !function_exists('tuple_min') )
{
	/**
	 * @param ... $values
	 * @return mixed
	 */
	function tuple_min( $values )
	{
		$args = array_filter_key( func_get_args(), not_dg( eq_dg( tuple_get(), return_dg(null) ) ) );
		$argsCount = count($args);
		if( $argsCount<1 )
		{
			$ret =  null;
		}
		elseif( $argsCount === 1 )
		{
			$ret = array_shift($args);
		}
		else
		{
			$ret = call_user_func_array( 'min', $args );
		}
		return $ret;
	}
}

if( !function_exists('tuple_min_dg') )
{
	/**
	 * @param ... $getters
	 * @return callable
	 */
	function tuple_min_dg( $getters )
	{
		$predicates = func_get_args();
		return function()use($predicates)
		{
			$args2 = func_get_args();
			return call_user_func_array(
				'tuple_min',
				array_map_val(
					$predicates,
					function( $arg, $n )use($args2)
					{
						if( is_callable($arg) )
						{
							return call_user_func_array( $arg, $args2 );
						}
						elseif( is_null($arg) )
						{
							return $args2[ $n ];
						}
						else
						{
							return $arg;
						}
					}
				)
			);
		};
	}
}

if( !function_exists('tuple_max') )
{
	/**
	 * @param ... $values
	 * @return mixed
	 */
	function tuple_max( $values )
	{
		$args = array_filter_key( func_get_args(), not_dg( eq_dg( tuple_get(), return_dg(null) ) ) );
		$argsCount = count($args);
		if( $argsCount<1 )
		{
			$ret =  null;
		}
		elseif( $argsCount === 1 )
		{
			$ret = array_shift($args);
		}
		else
		{
			$ret = call_user_func_array( 'max', $args );
		}
		return $ret;
	}
}

if( !function_exists('tuple_max_dg') )
{
	/**
	 * @param ... $getters
	 * @return callable
	 */
	function tuple_max_dg( $getters )
	{
		$predicates = func_get_args();
		return function()use($predicates)
		{
			$args2 = func_get_args();
			return call_user_func_array(
				'tuple_max',
				array_map_val(
					$predicates,
					function( $arg, $n )use($args2)
					{
						if( is_callable($arg) )
						{
							return call_user_func_array( $arg, $args2 );
						}
						elseif( is_null($arg) )
						{
							return $args2[ $n ];
						}
						else
						{
							return $arg;
						}
					}
				)
			);
		};
	}
}

if( !function_exists('collection_map_val_recursive') )
{
	/**
	 * @param array|object $collection
	 * @param callable $callable
	 * @param bool $recursionFirst
	 * @return array|object
	 * @throws Exception
	 */
	function collection_map_val_recursive($collection,$callable,$recursionFirst=true)
	{
		if( is_array($collection) )
		{
			$ret = array_map_val_recursive(
				$collection,
				function()use($callable,$recursionFirst)
				{
					$args = func_get_args();
					if( !$recursionFirst )
					{
						$args[0] = call_user_func_array( $callable, $args );
					}
					if( is_object($args[0]) || is_array($args[0]) )
					{
						$args[0] = collection_map_val_recursive( $args[0], $callable, $recursionFirst );
					}
					if( $recursionFirst )
					{
						$args[0] = call_user_func_array( $callable, $args );
					}
					return $args[0];
				},
				$recursionFirst
			);
		}
		elseif( is_object($collection) )
		{
			$ret = object_map_val_recursive(
				$collection,
				function()use($callable,$recursionFirst)
				{
					$args = func_get_args();
					if( !$recursionFirst )
					{
						$args[0] = call_user_func_array( $callable, $args );
					}
					if( is_array($args[0]) || is_object($args[0]) )
					{
						$args[0] = collection_map_val_recursive( $args[0], $callable, $recursionFirst );
					}
					if( $recursionFirst )
					{
						$args[0] = call_user_func_array( $callable, $args );
					}
					return $args[0];
				},
				$recursionFirst
			);
		}
		else
		{
			debug_enforce( false, "Cannot handle collection: ".var_dump_human_compact($collection) );
			$ret = [];
		}
		return $ret;
	}
}

if( !function_exists('max_execution_time_set_dg') )
{
	/**
	 * @param int|callable $seconds
	 * @return callable
	 */
	function max_execution_time_set_dg($seconds)
	{
		if( is_numeric($seconds) )
		{
			$seconds = return_dg( $seconds );
		}
		else
		{
			debug_enforce_type( $seconds, 'callable' );
		}
		return function()use($seconds)
		{
			$args = func_get_args();
			$key = 'max_execution_time';
			$ret = ini_get( $key );
			ini_set( $key, call_user_func_array( $seconds, $args ) );
			return $ret;
		};
	}
}

if( !function_exists('tuple_add_dg') )
{
	/**
	 * @param $arg1
	 * @param $arg2
	 * @return Closure
	 */
	function tuple_add_dg($arg1,$arg2)
	{
		$outerArgs = array_map_val( func_get_args(), function($arg){ return is_callable($arg) ? $arg : return_dg($arg); } );
		return function()use($outerArgs)
		{
			$innerArgs = func_get_args();
			return array_reduce_val(
				$outerArgs,
				function($carry, $outerArg)use( $innerArgs )
				{
					return $carry + call_user_func_array( $outerArg, $innerArgs );
				},
				0
			);
		};
	}
}

if( !function_exists('eq_dg') )
{
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
			return call_user_func_array($a,$args) == call_user_func_array($b,$args);
		};
	}
}

if( !function_exists('equals_dg') )
{
	/**
	 * @param callable $a
	 * @param callable $b
	 * @return callable
	 */
	function equals_dg($a,$b)
	{
		return function()use($a,$b)
		{
			$args = func_get_args();
			return call_user_func_array($a,$args) === call_user_func_array($b,$args);
		};
	}
}

if( !function_exists('lt_dg') )
{
	/**
	 * @param callable $a
	 * @param callable $b
	 * @return callable
	 */
	function lt_dg($a,$b)
	{
		return function()use($a,$b)
		{
			$args = func_get_args();
			return call_user_func_array($a,$args) < call_user_func_array($b,$args);
		};
	}
}

if( !function_exists('gt_dg') )
{
	/**
	 * @param callable $a
	 * @param callable $b
	 * @return callable
	 */
	function gt_dg($a,$b)
	{
		return function()use($a,$b)
		{
			$args = func_get_args();
			return call_user_func_array($a,$args) > call_user_func_array($b,$args);
		};
	}
}

if (!function_exists('coalesce')) {
    /**
     * @return string
     */
    function coalesce(&$var, $replacement)
    {
        return isset($var) ? $var : $replacement;
    }
}

if (!function_exists('removeNonUTF8Chars')) {
    /**
     * @return string
     */
    function removeNonUTF8Chars($text)
    {
        //reject overly long 2 byte sequences, as well as characters above U+10000
        $text = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' .
            '|[\x00-\x7F][\x80-\xBF]+' .
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '', $text);

         //reject overly long 3 byte sequences and UTF-16 surrogates
        $text = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]' .
            '|\xED[\xA0-\xBF][\x80-\xBF]/S', '?', $text);

        return $text;
    }
}