<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


if( !class_exists('Object') )
{
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
}

if( !function_exists('object') )
{
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
}

if( !function_exists('config_merge_recursive_overwrite') )
{
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
}

if( !function_exists('config_to_array_recursive') )
{
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
}

if( !function_exists('object_get') )
{
	/**
	 * @param object $object
	 * @param string $what
	 * @return mixed
	 */
	function object_get( $object, $what )
	{
		return $object->{$what};
	}
}

if( !function_exists('object_get_dg') )
{
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
}

if( !function_exists('object_map_val') )
{
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
}

if( !function_exists('object_filter_val') )
{
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
}

if( !function_exists('object_each') )
{
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
}

if( !function_exists('object_some') )
{
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
}

if( !function_exists('object_map_val_recursive') )
{
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
}

if( !function_exists('object_empty') )
{
	/**
	 * @param object $object
	 * @return bool
	 */
	function object_empty($object)
	{
		return !object_some( $object, return_dg(true) );
	}
}

if( !function_exists('instanceof_dg') )
{
	/**
	 * @param string|callable|null $class
	 * @param object|callable|null $object
	 *
	 * @return callable
	 */
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
}

if( !function_exists('new_dg') )
{
	/**
	 * @param callable|string $class
	 * @return mixed
	 */
	function new_dg($class)
	{
		if( !is_callable($class) )
		{
			$class = return_dg($class);
		}
		return function()use($class)
		{
			$args = func_get_args();
			$class = call_user_func_array( $class, $args );
			return new $class;
		};
	}
}

if( !function_exists('new_1_dg') )
{
	/**
	 * @param callable|string $class
	 * @param callable|mixed $arg
	 * @return mixed
	 */
	function new_1_dg($class,$arg)
	{
		if( !is_callable($class) )
		{
			$class = return_dg($class);
		}
		if( !is_callable($arg) )
		{
			$arg = return_dg($arg);
		}
		return function()use($class,$arg)
		{
			$args = func_get_args();
			$class = call_user_func_array( $class, $args );
			$arg = call_user_func_array( $arg, $args );
			return new $class($arg);
		};
	}
}

if( !function_exists('spl_object_hash') )
{
	function spl_object_hash(&$object)
	{
		static $hashes;

		if( !is_object($object) )
		{
			trigger_error(__FUNCTION__."() expects parameter 1 to be object", E_USER_WARNING);
			return null;
		}

		if( !isset($hashes) )
		{
			$hashes = array();
		}

		$class_name = get_class($object);
		if( !array_key_exists($class_name, $hashes) )
		{
			$hashes[$class_name] = array();
		}

		// find existing instance
		foreach($hashes[$class_name] as $hash => $o)
		{
			if( $object===$o )
			{
				return $hash;
			}
		}

		$hash = md5(uniqid($class_name));
		while( array_key_exists($hash, $hashes[$class_name]) )
		{
			$hash = md5(uniqid($class_name));
		}

		$hashes[$class_name][$hash] = $object;
		return $hash;
	}
}

if( !function_exists('get_called_class') )
{
	function get_called_class()
	{
		$bt = debug_backtrace();
		$l = 0;
		do
		{
			$l++;
			$lines = file($bt[$l]['file']);
			$callerLine = $lines[$bt[$l]['line']-1];
			preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/', $callerLine, $matches);

			if( $matches[1]=='self' )
			{
				$line = $bt[$l]['line']-1;
				while( $line>0 && strpos($lines[$line], 'class')===false )
				{
					$line--;
				}
				preg_match('/class[\s]+(.+?)[\s]+/si', $lines[$line], $matches);
			}
		} while( $matches[1]=='parent' && $matches[1] );
		return $matches[1];
	}
}
