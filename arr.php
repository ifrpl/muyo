<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/**
 * @param array $target
 * @param string|int|float $target_key
 * @param array $to_insert
 */
function array_insert_before(&$target,$target_key,$to_insert)
{
	$tmp = array();
	foreach($target as $k=>$v)
	{
		$tmp[$k] = $v;
		unset($target[$k]);
	}
	foreach($tmp as $k=>$v)
	{
		if( $k === $target_key )
		{
			foreach( $to_insert as $ik=>$iv )
			{
				$target[$ik] = $iv;
			}
		}
		$target[$k] = $v;
	}
}

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

	if( is_array($needle) )
	{
		return array_some( $needle, function($val) use ($array,$strict)
		{
			return in_array($val,$array,$strict);
		} );
	}
	else
	{
		return in_array($needle,$array,$strict);
	}
}
/**
 * @param mixed $needle
 * @return callable
 */
function array_contains_dg($needle)
{
	return function($array)use($needle)
	{
		return array_contains($array,$needle);
	};
}

/**
 * TODO: Think about better tuple chaining (composition method).
 *
 * @param mixed $needle
 * @return callable
 */
function array_not_contains_dg($needle)
{
	return function($array)use($needle)
	{
		return !array_contains($array,$needle);
	};
}


/**
 * @param array $array
 * @param callable $iterator
 *
 * @return bool
 */
function array_some($array, $iterator)
{
	foreach($array as $k => $v)
	{
		if( $iterator($v,$k) )
		{
			return true;
		}
	}
	return false;
}

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

/**
 * @param array $array
 * @param callable $iterator
 * @param array|null $keyspace
 *
 * @return array
 */
function array_group($array, $iterator, $keyspace = null)
{
	$ret = array();

	if( !is_null($keyspace) )
	{
		foreach( $keyspace as $key )
		{
			$ret[$key] = array();
		}
	}

	foreach( $array as $origKey=>$value )
	{
		$key = $iterator($value,$origKey);
		if( is_bool($key) )
		{
			$key = (int) $key; // well, i don't exactly feel like it's a sane limitation so try to workaround
		}
		if( !array_key_exists($key,$ret) )
		{
			$ret[$key] = array();
		}
		$ret[$key][$origKey] = $value;
	}

	return $ret;
}

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
		$mapped = array_map($iterator, $values, $keys);
		return array_combine($keys, $mapped);
	}
	else
	{
		return array();
	}
}

/**
 * @param array $array
 * @param callable $iterator function($val,$key)
 *
 * @return array
 */
function array_map_val_recursive($array, $iterator)
{
	$keys = array_keys($array);
	$values = array_values($array);
	$mapped = array_map(function($value, $key) use($iterator)
	{
		if(is_array($value))
		{
			$value = array_map_val_recursive($value, $iterator);
		}
		$value = $iterator($value, $key);
		return $value;
	},$values,$keys);
	return array_combine($keys,$mapped);
}

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

/**
 * Warning: Opposed to {@see array_uniq} it doesn't preserve original keys
 *
 * @param array $list
 * @param callable $iterator
 *
 * @return array
 */
function list_uniq($list, $iterator)
{
	return array_values(array_uniq($list,$iterator));
}

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

/**
 * @param mixed $val
 * @return bool
 */
function is_array_assoc($val)
{
	return is_array($val) && array_some($val,tuple_get(1,'is_string'));
}

/**
 * @param mixed $val
 * @return bool
 */
function is_array_list($val)
{
	return is_array($val) && array_some($val,tuple_get(1,'is_int'));
}

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

/**
 * Returns [0..count]
 *
 * @param array $array
 * @param int $count
 *
 * @return array
 */
function array_first($array,$count = 1)
{
	return array_slice($array,0,$count,is_array_assoc($array));
}

/**
 * Returns [0..count]
 *
 * @param int $count
 *
 * @return callable
 */
function array_first_dg($count = 1)
{
	return function($array)use($count)
	{
		return array_first($array,$count);
	};
}

/**
 * Returns [0..$-idx]
 *
 * @param array $array
 * @param int $idx
 *
 * @return array
 */
function array_initial($array,$idx = 1)
{
	return array_slice($array,0,-$idx,null,is_array_assoc($array));
}

/**
 * Returns [0..$-idx]
 *
 * @param int $idx
 *
 * @return callable
 */
function array_initial_dg($idx = 1)
{
	return function($array)use($idx)
	{
		return array_initial($array,$idx);
	};
}

/**
 * Returns [$-count..$]
 *
 * @param array $array
 * @param int $count
 *
 * @return array
 */
function array_last($array,$count = 1)
{
	return array_slice($array,-$count,null,is_array_assoc($array));
}

/**
 * Returns [$-count..$]
 *
 * @param int $count
 *
 * @return callable
 */
function array_last_dg($count = 1)
{
	return function($array)use($count)
	{
		return array_last($array,$count);
	};
}

/**
 * Returns [idx..$]
 *
 * @param array $array
 * @param int $idx
 *
 * @return array
 */
function array_rest($array,$idx = 1)
{
	return array_slice($array,$idx,null,is_array_assoc($array));
}

/**
 * Returns [idx..$]
 *
 * @param int $idx
 *
 * @return callable
 */
function array_rest_dg($idx = 1)
{
	return function()use($idx)
	{
		$array = func_get_args();
		$array = array_shift( $array );
		return array_rest( $array, $idx );
	};
}

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

/**
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

/**
 * @param array $array
 * @param callable $callable
 */
function array_each( $array, $callable )
{
	array_walk( $array, $callable );
}

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

/**
 * @param string $separator
 * @return callable
 */
function array_implode_dg( $separator )
{
	return function( $array )use($separator)
	{
		return implode( $separator, $array );
	};
}

/**
 * @param callable $callable
 * @param null|mixed $keyspace
 * @return callable
 */
function array_group_dg( $callable, $keyspace=null )
{
	return function( $array )use($callable,$keyspace)
	{
		return array_group( $array, $callable, $keyspace );
	};
}

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

/**
 * @param $callable
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