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
	public function __construct($array=[])
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
function object($array=[])
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
 * @return array
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

/**
 * @param object $object
 * @param string $what
 * @return mixed
 */
function object_get( $object, $what )
{
	return $object->{$what};
}

/**
 * @param callable $object
 * @param string|callable $what
 * @return callable
 */
function object_get_dg( $object, $what )
{
	if( is_string($what) )
	{
		$what = return_dg( $what );
	}
	if( debug_assert_type( $object, 'callable' ) && debug_assert( $what, 'callable' ) )
	{
		return function()use( $object, $what )
		{
			return object_get( $object(), $what() );
		};
	}
	else
	{
		return function()
		{
			return null;
		};
	}
}

/**
* @param object $config
 * @param callable $callable
 * @return object
 */
function object_map_val($config,$callable)
{
	return (object) array_map_val(
		(array) $config,
		function()use($callable)
		{
			return call_user_func_array( $callable, func_get_args() );
		}
	);
}

/**
 * @param object $config
 * @param callable $callable
 * @return object
 */
function object_filter_val($config,$callable)
{
	return (object) array_filter_key(
		(array) $config,
		function()use($callable)
		{
			return call_user_func_array( $callable, func_get_args() );
		}
	);
}

/**
 * @param object $object
 * @param callable $callable
 */
function object_each($object,$callable)
{
	array_each(
		(array) $object,
		function()use($callable)
		{
			call_user_func_array( $callable, func_get_args() );
		}
	);
}

/**
 * @param object $object
 * @param callable $callable
 * @return object
 */
function object_some($object,$callable)
{
	foreach( $object as $k => $v )
	{
		if( $callable($v,$k) )
		{
			return true;
		}
	}
	return false;
}

/**
 * @param object $config
 * @param callable $callable
 * @param bool $recursionFirst
 * @return object
 */
function object_map_val_recursive($config,$callable,$recursionFirst=true)
{
	$ret = clone($config);
	foreach ($config as $key=>$val)
	{
		if( !$recursionFirst )
		{
			$ret->{$key} = call_user_func( $callable, $val, $key );
		}
		if( is_object($val) )
		{
			$val = object_map_val_recursive( $val, $callable );
		}
		if( $recursionFirst )
		{
			$ret->{$key} = call_user_func( $callable, $val, $key );
		}
	}
	return $ret;
}

/**
 * @param object $object
 * @return bool
 */
function object_empty($object)
{
	return !object_some( $object, return_dg(true) );
}

function instanceof_dg($class=null,$object=null)
{
	if( is_null($object) )
	{
		$object = tuple_get(0);
	}
	elseif( is_object($object) )
	{
		$object = return_dg($object);
	}
	else
	{
		debug_enforce_type( $object, 'callable' );
	}

	if( is_null( $class ) )
	{
		$class = tuple_get(1);
	}
	elseif( is_string( $class ) )
	{
		$class = return_dg( $class );
	}
	else
	{
		debug_enforce_type( $class, 'callable' );
	}

	return function()use($object,$class)
	{
		$args = func_get_args();
		$object = call_user_func_array( $object, $args );
		$class = call_user_func_array( $class, $args );
		return $object instanceof $class;
	};
}