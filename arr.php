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
 * @param $arr1
 * @param $arr2
 *
 * @return mixed
 */
function array_merge_recursive_overwrite($arr1, $arr2)
{
	assert(is_array($arr1) && is_array($arr2));
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
 * @param $var
 * @return array
 */
function arrayize(&$var)
{
	if(!is_array($var))
	{
		if(is_object($var))
		{
			$var = (array)($var);
		}
		else
		{
			$var = array($var);
		}
	}
	return $var;
}
