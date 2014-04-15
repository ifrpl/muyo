<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


class Object
{
	/**
	 * @param array $array
	 */
	public function __construct($array=array())
	{
		foreach($array as $key=>$value)
		{
			if(is_array($value))
			{
				$value = new Object($value);
			}
			$this->$key = $value;
		}
	}

	/**
	 * @param $name
	 *
	 * @return null
	 */
	public function __get($name)
	{
		return isset($this->{$name})?$this->{$name}:null;
	}
}

/**
 * @param array $array
 *
 * @return object
 */
function object($array=array())
{
	$obj = ((object) NULL);
	foreach($array as $key=>$value)
	{
		if(is_array($value))
		{
			$value = object($value);
		}
		$obj->$key = $value;
	}
	return $obj;
}
/**
 * @param Object $config overwritten target config
 * @param Array $arr source
 *
 * @return Object
 */
function config_merge_recursive_overwrite($config, $arr)
{
	foreach($arr as $key=>$value)
	{
		if(isset($config->$key) && is_array($value))
		{
			$config->$key = config_merge_recursive_overwrite($config->$key, $value);
		}
		else
		{
			if(!empty($value))
			{
				$config->$key = $value;
			}
		}
	}

	return $config;
}

/**
 * @param stdClass $config
 *
 * @return stdClass
 */
function config_to_array_recursive($config) {
	if( is_object($config) && 'stdClass' === get_class($config) )
	{
		$config = (array) $config;
	}
	if( is_array($config) )
	{
		foreach( $config as $k => $v )
		{
			$config[$k] = config_to_array_recursive($v);
		}
	}
	return $config;
}