<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)

require_once __DIR__.'/debug.php';
require_once __DIR__.'/misc.php';

if( !function_exists('array_dg') )
{
	/**
	 * @param mixed ...
	 * @return callable
	 */
	function array_dg()
	{
		$array = array_map_val(
			func_get_args(),
			function($arg)
			{
				if( !is_callable($arg) )
				{
					$ret = return_dg($arg);
				}
				else
				{
					$ret = $arg;
				}
				return $ret;
			}
		);
		return function()use($array)
		{
			$args = func_get_args();
			return array_map_val(
				$array,
				function($val)use($args)
				{
					return call_user_func_array( $val, $args );
				}
			);
		};
	}
}

if( !function_exists('array_insert_') )
{
	/**
	 * @param array $target
	 * @param string|int|float $target_key
	 * @param array $to_insert
	 * @param boolean $after
	 */
	function array_insert_(&$target,$target_key,$to_insert, $after)
	{
		$tmp = [];

		foreach($target as $k=>$v)
		{
			if($after)
			{
				$tmp[$k] = $v;
			}

			if( $k === $target_key )
			{
				foreach( $to_insert as $ik=>$iv )
				{
					$tmp[$ik] = $iv;
				}
			}

			if(!$after)
			{
				$tmp[$k] = $v;
			}
		}

		$target = $tmp;
	}
}

if( !function_exists('array_insert_before') )
{
	/**
	 * @param array $target
	 * @param string|int|float $target_key
	 * @param array $to_insert
	 */
	function array_insert_before(&$target,$target_key,$to_insert)
	{
		array_insert_($target,$target_key,$to_insert, false);
	}
}

if( !function_exists('array_insert_after') )
{
	/**
	 * @param array $target
	 * @param string|int|float $target_key
	 * @param array $to_insert
	 */
	function array_insert_after(&$target,$target_key,$to_insert)
	{
		array_insert_($target,$target_key,$to_insert, true);
	}
}

if( !function_exists('array_qsort') )
{
	/**
	 * @param     $array
	 * @param int $column
	 * @param int $order
	 */
	function array_qsort(&$array, $column=0, $order=SORT_ASC)
	{
		$dst = array();
		$sort = array();

		foreach($array as $key => $value)
		{
			if(is_array($value))
			{
				$sort[$key] = $value[$column];
			}
			else
			{
				$sort[$key] = $value->$column;
			}
		}
		if($order == SORT_ASC)
		{
			asort($sort);
		}
		else
		{
			arsort($sort);
		}

		foreach($sort as $key=>$value)
		{
			$dst[(string)$key] = $array[$key];
		}
		$array = $dst;
	}
}

if( !function_exists('array_merge_recursive_overwrite') )
{
	/**
	 * @param array $arr1
	 * @param array $arr2
	 *
	 * @return array
	 */
	function array_merge_recursive_overwrite($arr1, $arr2)
	{
		if( debug_assert(is_array($arr1) && is_array($arr2)) )
		{
			foreach($arr2 as $key=>$value)
			{
				if(array_key_exists($key, $arr1) && is_array($value))
				{
					$arr1[$key] = array_merge_recursive_overwrite($arr1[$key], $arr2[$key]);
				}
				else
				{
					if(isset($value))
					{
						$arr1[$key] = $value;
					}
				}
			}
		}

		return $arr1;
	}
}

if( !function_exists('arrayize') )
{
	/**
	 * @param mixed $var
	 * @return array
	 */
	function arrayize(&$var)
	{
		if( !is_array($var) )
		{
			$var = array($var);
		}
		return $var;
	}
}

if( !function_exists('arrayize_dg') )
{
	/**
	 * @return callable
	 */
	function arrayize_dg()
	{
		return function($value)
		{
			return arrayize( $value );
		};
	}
}

if( !function_exists('array_key_is_reference') )
{
	/**
	 * @param array      $arr
	 * @param string|int $key
	 *
	 * @return bool
	 */
	function array_key_is_reference($arr, $key)
	{
		$isRef = false;
		ob_start();
		var_dump($arr);
		if(
			false !== strpos(
				preg_replace("/[ \n\r]*/i", "", preg_replace("/( ){4,}.*(\n\r)*/i", "", ob_get_contents())),
				"[".$key."]=>&"
			)
		)
		{
			$isRef = true;
		}
		ob_end_clean();
		return $isRef;
	}
}

if( !function_exists('array_contains') )
{
	/**
	 * @param array $array
	 * @param mixed $needle
	 * @param bool  $strict
	 * @return bool
	 * @see array_some for iterator version
	 */
	function array_contains($array, $needle, $strict = false)
	{
		debug_enforce( is_array($array), "Expected array" );

		return in_array($needle,$array,$strict);
	}
}

if( !function_exists('array_contains_dg') )
{
	/**
	 * @param mixed|callable $needle
	 * @param array|callable|null $array
	 * @return callable
	 */
	function array_contains_dg($needle, $array=null)
	{
		if( null===$array )
		{
			$array = tuple_get();
		}
		elseif( is_array($array) )
		{
			$array = return_dg($array);
		}
		else
		{
			debug_enforce_type( $array, 'callable' );
		}
		if( !is_callable($needle) )
		{
			$needle = return_dg($needle);
		}
		return function()use($array,$needle)
		{
			$args = func_get_args();
			return array_contains(
				call_user_func_array( $array, $args ),
				call_user_func_array( $needle, $args )
			);
		};
	}
}

if( !function_exists('in_array_dg') )
{
	/**
	 * @param array $array
	 * @param array|callable|null $needle
	 * @return callable
	 */
	function in_array_dg( $array, $needle=null )
	{
		if( is_array($array) )
		{
			$array = return_dg( $array );
		}
		else
		{
			debug_enforce( $array, 'callable' );
		}
		if( is_null($needle) )
		{
			$needle = tuple_get(0);
		}
		elseif( !is_callable($needle) )
		{
			$needle = return_dg($needle);
		}
		return array_contains_dg( $needle, $array );
	}
}

if( !function_exists('array_not_contains_dg') )
{
	/**
	 * @param mixed $needle
	 * @return callable
	 * @deprecated wrap with not_dg() instead
	 */
	function array_not_contains_dg($needle)
	{
		return function($array)use($needle)
		{
			return !array_contains($array,$needle);
		};
	}
}

if( !function_exists('array_some') )
{
	/**
	 * @param array $array
	 * @param callable $iterator
	 *
	 * @return bool
	 */
	function array_some($array, $iterator)
	{
		debug_enforce_type( $iterator, 'callable' );
		foreach($array as $k => $v)
		{
			if( $iterator($v,$k) )
			{
				return true;
			}
		}
		return false;
	}
}

if( !function_exists('array_find_val') )
{
	/**
	 * Finds first value matching the $iterator from the $array
	 *
	 * @param array $array
	 * @param callable $iterator
	 * @return mixed
	 */
	function array_find_val($array, $iterator)
	{
		foreach( $array as $k => $v )
		{
			if( $iterator($v,$k) )
			{
				return $v;
			}
		}
		return null;
	}
}

if( !function_exists('array_find_val_dg') )
{
	/**
	 * @param callable $iterator
	 * @return callable
	 */
	function array_find_val_dg( $iterator )
	{
		return function( $array )use( $iterator )
		{
			return array_find_val( $array, $iterator );
		};
	}
}

if( !function_exists('array_find_key') )
{
	/**
	 * Finds first key matching the $iterator from the $array
	 *
	 * @param array $array
	 * @param callable $iterator
	 * @return int|null|string
	 */
	function array_find_key($array, $iterator)
	{
		foreach( $array as $k => $v )
		{
			if( $iterator($v,$k) )
			{
				return $k;
			}
		}
		return null;
	}
}

if( !function_exists('array_all') )
{
	/**
	 * @param array $array
	 * @param callable $iterator
	 *
	 * @return bool
	 */
	function array_all($array, $iterator)
	{
		foreach($array as $k => $v)
		{
			if( !$iterator($v,$k) )
			{
				return false;
			}
		}
		return true;
	}
}

if( !function_exists('array_all_dg') )
{
	/**
	 * @param callable $iterator
	 * @return callable
	 */
	function array_all_dg( $iterator )
	{
		return function( $array )use($iterator)
		{
			return array_all( $array, $iterator );
		};
	}
}

if( !function_exists('array_group') )
{
	/**
	 * @param array $array
	 * @param callable $iterator
	 *
	 * @return array
	 */
	function array_group($array, $iterator)
	{
		$ret = [];

		foreach( $array as $origKey=>$value )
		{
			$key = $iterator($value,$origKey);
			if( is_bool($key) )
			{
				$key = (int) $key; // well, i don't exactly feel like it's a sane limitation so try to workaround
			}
			if( !array_key_exists($key,$ret) )
			{
				$ret[$key] = [];
			}
			$ret[$key][$origKey] = $value;
		}

		return $ret;
	}
}

if( !function_exists('array_group_dg') )
{
	/**
	 * @param callable $callable
	 * @return callable
	 */
	function array_group_dg( $callable )
	{
		return function( $array )use($callable)
		{
			return array_group( $array, $callable );
		};
	}
}

if( !function_exists('array_chain') )
{
	/**
	 * @param array $array
	 * @param callable $iterator,...
	 *
	 * @return array
	 */
	function array_chain($array, $iterator)
	{
		$args = func_get_args();
		$array = array_shift($args);
		while( !empty($args) )
		{
			/** @var callable $iterator */
			$iterator = array_shift($args);
			$array = $iterator($array);
		}
		return $array;
	}
}

if( !function_exists('array_chain_dg') )
{
	/**
	 * @param array|callable $array_getter
	 * @param callable $iterators_getter WARNING: it's just a callable to chain on
	 * @return callable
	 */
	function array_chain_dg($array_getter, $iterators_getter)
	{
		$args = func_get_args();
		$array_getter = callablize( array_shift( $args ) );
		$iterators_getters = $args;
		return function()use( $array_getter, $iterators_getters )
		{
			$args = func_get_args();
			$array = call_user_func_array( $array_getter, $args );
			$ret = call_user_func_array( 'array_chain', array_merge( array($array), $iterators_getters ) );
			return $ret;
		};
	}
}

if( !function_exists('array_map_val') )
{
	/**
	 * @param array $array
	 * @param callable $iterator function($val,$key)
	 *
	 * @return array
	 */
	function array_map_val($array, $iterator)
	{
		if( debug_assert(is_array($array) && is_callable($iterator),'Invalid parameters') && !empty($array) )
		{
			$keys = array_keys($array);
			$values = array_values($array);
			debug_enforce_type( $iterator, 'callable' );
			debug_enforce_type( $values, 'array' );
			debug_enforce_type( $keys, 'array' );
			$mapped = array_map($iterator, $values, $keys);
			return array_combine($keys, $mapped);
		}
		else
		{
			return array();
		}
	}
}

if( !function_exists('array_map_val_dg') )
{
	/**
	 * @param callable $iterator
	 * @return callable
	 */
	function array_map_val_dg($iterator)
	{
		return function()use($iterator)
		{
			$array = func_get_args();
			$array = array_shift( $array );
			return array_map_val( $array, $iterator );
		};
	}
}

if( !function_exists('array_map_val_recursive') )
{
	/**
	 * @param array $array
	 * @param callable $iterator function($val,$key)
	 * @param bool $recursionFirst
	 *
	 * @return array
	 */
	function array_map_val_recursive($array, $iterator, $recursionFirst=true)
	{
		return array_map_val(
			$array,
			function()use($iterator,$recursionFirst)
			{
				$args = func_get_args();
				if( !$recursionFirst )
				{
					$args[0] = call_user_func_array( $iterator, $args );
				}
				if( is_array($args[0]) )
				{
					$args[0] = array_map_val_recursive( $args[0], $iterator );
				}
				if( $recursionFirst )
				{
					$args[0] = call_user_func_array( $iterator, $args );
				}
				return $args[0];
			}
		);
	}
}

if( !function_exists('array_map_key') )
{
	/**
	 * @param array $array
	 * @param callable $iterator function($key,$val)
	 *
	 * @return array
	 */
	function array_map_key($array, $iterator)
	{
		if(count($array))
		{
			$values = array_values($array);
			$mapped = array_map_val($array,$iterator);
			return array_combine($mapped,$values);
		}
		else
		{
			return array();
		}
	}
}

if( !function_exists('array_map_key_dg') )
{
	/**
	 * @param callable $iterator
	 * @return callable
	 */
	function array_map_key_dg($iterator)
	{
		return function()use($iterator)
		{
			$array = func_get_args();
			$array = array_shift( $array );
			return array_map_key( $array, $iterator );
		};
	}
}

if( !function_exists('array_uniq') )
{
	/**
	 * Warning: Opposed to {@see list_uniq} it preserves original keys
	 *
	 * @param array $array
	 * @param null|callable $iterator function($val,$key) returning uniqueness key
	 *
	 * @return array
	 */
	function array_uniq($array, $iterator=null)                //function($val,$key){ return $val===1||$val===2; }
	{                                                          //[1=>1,2=>2,3=>3]
		if( null===$iterator )
		{
			$iterator = function( $val )
			{
				return $val;
			};
		}
		$map = array_map_val($array,$iterator);                //[1=>true,2=>true,3=>false]
		$umap = array_unique($map);                            //[1=>true,3=>false]
		$ukeys = array_keys($umap);                            //[1=>1,2=>3]
		return array_intersect_key($array,array_flip($ukeys)); //[1=>1,3=>3]
	}
}

if( !function_exists('array_uniq_dg') )
{
	/**
	 * @param callable|null $iterator
	 * @return callable
	 */
	function array_uniq_dg($iterator=null)
	{
		return function ( $array ) use ( $iterator )
		{
			return array_uniq( $array, $iterator );
		};
	}
}

if( !function_exists('array_pluck') )
{
	/**
	 * @param array|object $array
	 * @param string|int $key
	 *
	 * @return array
	 */
	function array_pluck($array, $key)
	{
		return array_map_val(
			$array,
			function ( $collection ) use ( $key )
			{
				return is_object( $collection ) ? $collection->$key : $collection[ $key ];
			}
		);
	}
}

if( !function_exists('array_pluck_dg') )
{
	/**
	 * @param string $attribute
	 * @return callable
	 */
	function array_pluck_dg($attribute)
	{
		return function()use($attribute)
		{
			$array = func_get_args();
			$array = array_shift( $array );
			return array_pluck( $array, $attribute );
		};
	}
}

if( !function_exists('array_filter_key') )
{
	/**
	 * @param array $array
	 * @param callable $iterator
	 *
	 * @return array
	 */
	function array_filter_key($array,$iterator)
	{
		if( debug_assert(is_array($array) && is_callable($iterator),'Invalid parameters') )
		{
			$mapped = array_map_val($array,$iterator);
			$filtered = array_filter($mapped,function($val){ return true === $val; });
			return array_intersect_key($array,$filtered);
		}
		else
		{
			return array();
		}
	}
}

if( !function_exists('array_filter_key_dg') )
{
	/**
	 * @param callable $iterator
	 * @return callable
	 */
	function array_filter_key_dg($iterator)
	{
		return function($array)use($iterator)
		{
			return array_filter_key($array,$iterator);
		};
	}
}

if( !function_exists('array_filter_key_recursive') )
{
	/**
	 * @param array $array
	 * @param callable $callable
	 * @return array
	 */
	function array_filter_key_recursive($array, $callable)
	{
		return array_chain(
			$array,
			array_filter_key_dg( $callable ),
			array_map_val_dg(function( $val )use( $callable )
			{
				if( is_array($val) )
				{
					return array_filter_key_recursive( $val, $callable );
				}
				else
				{
					return $val;
				}
			} )
		);
	}
}

if( !function_exists('array_unset_val') )
{
	/**
	 * @param array $array
	 * @param mixed $val
	 * @param bool $strict
	 */
	function array_unset_val(&$array, $val, $strict = true)
	{
		foreach(array_keys($array, $val, $strict) as $key)
		{
			unset($array[$key]);
		}
	}
}

if( !function_exists('array_get') )
{
	/**
	 * @param array $array
	 * @param string|int $key
	 * @return mixed
	 */
	function array_get($array,$key)
	{
		debug_enforce(
			array_key_exists( $key, $array ),
			"Key ".var_dump_human_compact($key)." do not exists in ".var_dump_human_compact($array)
		);
		return $array[ $key ];
	}
}

if( !function_exists('array_get_dg') )
{

	/**
	 * @param callable|int|string $key
	 * @param callable|null $array
	 * @return callable
	 */
	function array_get_dg($key,$array=null)
	{
		if( is_null($array) )
		{
			$array = tuple_get(0);
		}
		elseif( !is_callable($array) )
		{
			$array = return_dg( $array );
		}
		if( !is_callable($key) )
		{
			$key = return_dg( $key );
		}
		return function()use($array,$key)
		{
			$args = func_get_args();
			$array = call_user_func_array( $array, $args );
			$key = call_user_func_array( $key, $args );
			return array_get( $array, $key );
		};
	}
}

if( !function_exists('array_set') )
{
	/**
	 * @param array $array
	 * @param string|int $key
	 * @param mixed $value
	 * @return array
	 */
	function array_set($array,$key,$value)
	{
		$array[ $key ] = $value;
		return $array;
	}
}

if( !function_exists('array_get_unset') )
{
	/**
	 * @param array $array
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	function array_get_unset(&$array, $key, $default = null)
	{
		debug_enforce( is_array($array), "array_get_unset expects first parameter to be array, ".gettype($array)." given" );
		$ret = array_key_exists($key, $array) ? $array[$key] : $default;
		unset($array[$key]);
		return $ret;
	}
}

if( !function_exists('array_set_default') )
{
	/**
	 * @param array $array
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	function array_set_default(&$array,$key,$value)
	{
		if( !array_key_exists($key,$array) )
		{
			$array[$key] = $value;
		}
		return $array[$key];
	}
}

if( !function_exists('is_array_assoc') )
{
	/**
	 * @param mixed $val
	 * @return bool
	 */
	function is_array_assoc($val)
	{
		if( !is_array($val) )
		{
			$ret = false;
		}
		else
		{
			if( empty($val) )
			{
				$ret = true; // WARNING: common for both type of arrays
			}
			else
			{
				$ret = array_some($val,tuple_get(1,'is_string'));
			}
		}
		return $ret;
	}
}

if( !function_exists('array_zip') )
{
	/**
	 * @param array... $arrays
	 * @return array
	 */
	function array_zip( /*$args*/ )
	{
		$args = func_get_args();
		$zipped = call_user_func_array('array_map', array_merge(array(null), $args));
		$trimmed = array_slice($zipped, 0, min(array_map('count', $args)));
		return $trimmed;
	}
}

if( !function_exists('array_key_exists_dg') )
{
	/**
	 * @param string|null $key
	 * @return callable
	 */
	function array_key_exists_dg($key=null)
	{
		if( null !== $key )
		{
			return function()use($key)
			{
				$array = func_get_args();
				$array = array_shift( $array );
				return array_key_exists( $key, $array );
			};
		}
		else
		{
			return function($array,$key)
			{
				return array_key_exists($key,$array);
			};
		}
	}
}

if( !function_exists('array_first') )
{
	/**
	 * Returns [0..n]
	 *
	 * @param array $array
	 * @param int $n
	 *
	 * @return array
	 */
	function array_first($array,$n = 1)
	{
		return array_slice($array,0,$n,is_array_assoc($array));
	}
}

if( !function_exists('array_first_dg') )
{
	/**
	 * Returns [0..n]
	 *
	 * @param int $n
	 * @return callable
	 */
	function array_first_dg($n = 1)
	{
		return function($array)use($n)
		{
			return array_first($array,$n);
		};
	}
}

if( !function_exists('array_initial') )
{
	/**
	 * Returns [0..$-n]
	 *
	 * @param array $array
	 * @param int $n
	 *
	 * @return array
	 */
	function array_initial($array,$n = 1)
	{
		return array_slice($array,0,-$n,is_array_assoc($array));
	}
}

if( !function_exists('array_initial_dg') )
{
	/**
	 * Returns [0..$-n]
	 *
	 * @param int $n
	 *
	 * @return callable
	 */
	function array_initial_dg($n = 1)
	{
		return function($array)use($n)
		{
			return array_initial($array,$n);
		};
	}
}

if( !function_exists('array_last') )
{
	/**
	 * Returns [$-n..$]
	 *
	 * @param array $array
	 * @param int $n
	 *
	 * @return array
	 */
	function array_last($array,$n = 1)
	{
		return array_slice($array,-$n,null,is_array_assoc($array));
	}
}

if( !function_exists('array_last_dg') )
{
	/**
	 * Returns [$-n..$]
	 *
	 * @param int $n
	 *
	 * @return callable
	 */
	function array_last_dg($n = 1)
	{
		return function($array)use($n)
		{
			return array_last($array,$n);
		};
	}
}

if( !function_exists('array_rest') )
{
	/**
	 * Returns [n..$]
	 *
	 * @param array $array
	 * @param int $n
	 *
	 * @return array
	 */
	function array_rest($array,$n = 1)
	{
		return array_slice($array,$n,null,is_array_assoc($array));
	}
}

if( !function_exists('array_rest_dg') )
{
	/**
	 * Returns [n..$]
	 *
	 * @param int $n
	 *
	 * @return callable
	 */
	function array_rest_dg($n = 1)
	{
		return function()use($n)
		{
			$array = func_get_args();
			$array = array_shift( $array );
			return array_rest( $array, $n );
		};
	}
}

if( !function_exists('array_flatten') )
{
	/**
	 * [[1,2,3],[a,b,c],[]] => [1,2,3,a,b,c]
	 *
	 * @param $array
	 * @return array
	 */
	function array_flatten($array)
	{
		$array = array_map_val($array,function()
		{
			$array = func_get_args();
			$array = array_shift( $array );
			return arrayize( $array );
		});
		return array_reduce($array,'array_merge',array());
	}
}

if( !function_exists('array_flatten_dg') )
{
	/**
	 * [[1,2,3],[a,b,c],[]] => [1,2,3,a,b,c]
	 *
	 * @return callable
	 */
	function array_flatten_dg()
	{
		return function($array)
		{
			return array_flatten($array);
		};
	}
}

if( !function_exists('array_flatten_recursive') )
{
	/**
	 * @param $array
	 * @return array
	 */
	function array_flatten_recursive($array)
	{
		$array = array_map_val($array,function()
		{
			$array = func_get_args();
			$array = array_shift( $array );
			return arrayize( $array );
		});
		return array_reduce($array,'array_merge_recursive');
	}
}

if( !function_exists('array_flatten_recursive_dg') )
{
	/**
	 * @return callable
	 */
	function array_flatten_recursive_dg()
	{
		return function()
		{
			$array = func_get_args();
			$array = array_shift( $array );
			return array_flatten_recursive($array);
		};
	}
}

if( !function_exists('array_search_recursive') )
{
	/**
	 * Returns key of value, key of array which contains value or false.
	 *
	 * @param array $haystack
	 * @param mixed $needle
	 *
	 * @return int|string|bool
	 */
	function array_search_recursive($haystack, $needle)
	{
		foreach($haystack as $key => $value)
		{
			$current_key = $key;
			if( $needle===$value || (is_array($value) && array_search_recursive($needle, $value)!==false) )
			{
				return $current_key;
			}
		}
		return false;
	}
}

if( !function_exists('array_search_by_key_recursive') )
{
	/**
	 * Returns first value identified by key existing in $haystack (or contained subarrays).
	 *
	 * @param array $haystack
	 * @param mixed $needle
	 *
	 * @return mixed
	 */
	function array_search_by_key_recursive($haystack, $needle)
	{
		foreach($haystack as $key => $value)
		{
			if( $needle===$key )
			{
				return $value;
			}
			elseif( is_array($value) )
			{
				$result = array_search_by_key_recursive($needle, $value);
				if( $result!==false )
				{
					return $result;
				}
			}
		}
		return false;
	}
}

if( !function_exists('array_debug_dg') )
{
	/**
	 * TODO: removal of debug.php dependency
	 * @return callable
	 */
	function array_debug_dg()
	{
		return function()
		{
			$array = func_get_args();
			$val = array_shift( $array );
			debug($val);
			return $val;
		};
	}
}

if( !function_exists('array_merge_alt') )
{
	/**
	 * @param $array0
	 * @param $array1
	 */
	function array_merge_alt(&$array0, $array1)
	{
		foreach($array1 as $key => $value)
		{
			$array0[$key] = $value;
		}
	}
}

if( !function_exists('array_ksort_dg') )
{
	/**
	 * @param null $sortFlags
	 * @return callable function($array)
	 */
	function array_ksort_dg( $sortFlags=null )
	{
		return function($array)use($sortFlags)
		{
			ksort($array,$sortFlags);
			return $array;
		};
	}
}

if( !function_exists('array_each') )
{
	/**
	 * @param array $array
	 * @param callable $callable
	 */
	function array_each( $array, $callable )
	{
		array_walk( $array, $callable );
	}
}

if( !function_exists('array_each_dg') )
{
	/**
	 * @param $callable
	 * @return callable
	 */
	function array_each_dg( $callable )
	{
		return function( $array )use($callable)
		{
			array_each( $array, $callable );
		};
	}
}

if( !function_exists('array_walk_dg') )
{
	/**
	 * @param callable $callable
	 * @param mixed $userData
	 * @return callable function($array)
	 */
	function array_walk_dg( $callable, $userData=null )
	{
		return function( $array )use( $callable, $userData )
		{
			debug_enforce( array_walk( $array, $callable, $userData ), 'array_walk failure' );
			return $array;
		};
	}
}

if( !function_exists('array_implode_dg') )
{
	/**
	 * @param string|callable|null $separator
	 * @param array|callable|null
	 * @return callable
	 */
	function array_implode_dg( $separator=null, $array=null )
	{
		if( null===$separator )
		{
			$separator = return_dg(PHP_EOL);
		}
		elseif( !is_callable($separator) )
		{
			$separator = return_dg($separator);
		}

		if( is_null($array) )
		{
			$array = tuple_get(0);
		}
		elseif( is_array($array) )
		{
			$array = return_dg($array);
		}
		else
		{
			debug_enforce_type( $array, 'callable' );
		}

		return function()use($separator,$array)
		{
			$args = func_get_args();
			return implode(
				call_user_func_array($separator,$args),
				call_user_func_array($array,$args)
			);
		};
	}
}

if( !function_exists('array_keys_dg') )
{
	/**
	 * @return callable
	 */
	function array_keys_dg()
	{
		return function( $array )
		{
			return array_keys( $array );
		};
	}
}

if( !function_exists('array_values_dg') )
{
	/**
	 * @return callable
	 */
	function array_values_dg()
	{
		return function( $array )
		{
			return array_values( $array );
		};
	}
}

if( !function_exists('array_reduce_val') )
{
	/**
	 * @param array $array
	 * @param callable $callable mixed function($item1Val,$item2Val,$item2Key)
	 * @param mixed $startValue
	 * @return mixed
	 */
	function array_reduce_val( $array, $callable, $startValue=null )
	{
		// PHP array_reduce is list_reduce in our terminology
		foreach( $array as $key=>$val )
		{
			$startValue = $callable( $startValue, $val, $key );
		}
		return $startValue;
	}
}

if( !function_exists('array_reduce_val_dg') )
{
	/**
	 * @param $callable mixed function($item1Val,$item2Val,$item2Key)
	 * @param mixed $startValue
	 * @return callable
	 */
	function array_reduce_val_dg( $callable, $startValue=null )
	{
		return function ( $array ) use ( $callable, $startValue )
		{
			return array_reduce_val( $array, $callable, $startValue );
		};
	}
}

if( !function_exists('array_union') )
{
	/**
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 */
	function array_union( $array1, $array2 )
	{
		$union = $array1+$array2;
		return array_uniq( $union );
	}
}

if( !function_exists('array_union_dg') )
{
	/**
	 * @param array $array2
	 * @return callable
	 */
	function array_union_dg( $array2 )
	{
		return function ( $array1 ) use ( $array2 )
		{
			return array_union( $array1, $array2 );
		};
	}
}

if( !function_exists('array_sort') )
{
	/**
	 * @param $array
	 * @param $callable
	 * @return array
	 */
	function array_sort( $array, $callable )
	{
		$mapped = array_map_val( $array, $callable );
		asort( $mapped );
		$ret = array_map_val( $mapped, function($sortKey,$key)use($array)
		{
			return $array[ $key ];
		} );
		return $ret;
	}
}

if( !function_exists('array_sort_dg') )
{
	/**
	 * @param $callable
	 * @return callable
	 */
	function array_sort_dg( $callable )
	{
		return function( $array )use( $callable )
		{
			return array_sort( $array, $callable );
		};
	}
}

if( !function_exists('uasort_dg') )
{
	/**
	 * @param callable $comparator
	 * @return callable
	 */
	function uasort_dg( $comparator )
	{
		return function( $array )use( $comparator )
		{
			uasort( $array, $comparator );
			return $array;
		};
	}
}

if( !function_exists('array_join') )
{
	/**
	 *
	 * @param $array0
	 * @param $array1
	 *
	 * @return array
	 */
	function array_join($array0, $array1, $preserveKey = true)
	{
		$ret = array();

		foreach($array0 as $key0 => $value0)
		{
			if(!isset($array1[$value0]))
			{
				continue;
			}

			if(!$preserveKey)
			{
				$key0 = $value0;
			}

			$ret[$key0] = $array1[$value0] ;
		}

		return $ret;
	}
}

if( !function_exists('array_append') )
{
	/**
	 * @param array $item
	 * @param string|int $key
	 * @param array $array
	 * @return array
	 */
	function array_append(&$item, $key, $array)
	{
		if( is_array($item) )
		{
			if( isset($array[$key]) )
			{
				array_walk($item, "array_append", $array[$key]);
			}
		}
		elseif( isset($array[$key]) )
		{
			$item = $array[$key];
		}
		return $item;
	}
}

if( !function_exists('count_dg') )
{
	/**
	* @return callable
	 */
	function count_dg()
	{
		return function($array)
		{
			return count($array);
		};
	}
}

if( !function_exists('array_eq') )
{
	/**
	 * @param array $array1
	 * @param array $array2,...
	 * @return bool
	 */
	function array_eq( $array1, $array2 )
	{
		$diff = call_user_func_array(
			'array_diff',
			func_get_args()
		);
		return empty($diff);
	}
}