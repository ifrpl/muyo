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
	debug_assert(is_array($arr1) && is_array($arr2));
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
 * @param array $array
 * @param callable $iterator
 * @param array $key_space
 *
 * @return array
 */
function array_group($array, $iterator, $key_space = null)
{
	$ret = array();

	if( !is_null($key_space) )
	{
		foreach( $key_space as $key )
		{
			$ret[$key] = array();
		}
	}

	foreach( $array as $value )
	{
		$key = $iterator($value);
		if( !isset($ret[$key]) )
		{
			$ret[$key] = array();
		}
		$ret[$key] []= $value;
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