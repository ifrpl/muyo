<?php

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
	if(!is_array($var))
	{
		$var = array($var);
	}
	return $var;
}

/**
 * @param array $arr
 * @param string|int $key
 *
 * @return bool
 */
function array_key_is_reference($arr, $key)
{
	$isRef = false;
	ob_start();
	var_dump($arr);
	if (strpos(preg_replace("/[ \n\r]*/i", "", preg_replace("/( ){4,}.*(\n\r)*/i", "", ob_get_contents())), "[" . $key . "]=>&") !== false)
	{
		$isRef = true;
	}
	ob_end_clean();
	return $isRef;
}

/**
 * @param      $arr
 * @param      $needle
 * @param bool $strict
 *
 * @return bool
 */
function array_contains($arr, $needle, $strict = false)
{
	return false !== array_search($needle, $arr, $strict);
}

/**
 * @param array $arr
 * @param callable $iterator
 *
 * @return bool
 */
function array_some($arr, $iterator)
{
	foreach($arr as $k => $v)
	{
		if( $iterator($v,$k) )
		{
			return true;
		}
	}
	return false;
}

/**
 * @param array $arr
 * @param callable $iterator
 *
 * @return bool
 */
function array_all($arr, $iterator)
{
	foreach($arr as $k => $v)
	{
		if( !$iterator($v,$k) )
		{
			return false;
		}
	}
	return true;
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
		$mapped = array_map($iterator,$values,$keys);
		return array_combine($keys,$mapped);
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
 * Warning: Opposed to {@see list_uniq} it preserves original keys
 * @param array $array
 * @param callable $iterator function($val,$key) returning uniqueness key
 *
 * @return array
 */
function array_uniq($array, $iterator)                   //function($val,$key){ return $val===1||$val===2; }
{                                                        //[1=>1,2=>2,3=>3]
	$map = array_map_val($array,$iterator);                //[1=>true,2=>true,3=>false]
	$umap = array_unique($map);                            //[1=>true,3=>false]
	$ukeys = array_keys($umap);                            //[1=>1,2=>3]
	return array_intersect_key($array,array_flip($ukeys)); //[1=>1,3=>3]
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
 * @param array $array
 * @param string|int $key
 *
 * @return array
 */
function array_pluck($array, $key)
{
	return array_map_val($array, function($collection)use($key){ return is_object($collection) ? $collection->$key : $collection[$key]; });
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
 * @param array $array
 * @param string $key
 * @return mixed
 */
function array_get_unset(&$array,$key)
{
	$ret = array_key_exists($key,$array) ? $array[$key] : null;
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