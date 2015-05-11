<?php
//          Copyright IF Research Sp. z o.o. 2015.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


if( !function_exists('mutate_include_path') )
{
	/**
	 * @param callable $callable
	 */
	function include_path_mutate($callable)
	{
		$path = explode(PATH_SEPARATOR,get_include_path());
		$path = call_user_func($callable,$path);
		set_include_path(implode(PATH_SEPARATOR,$path));
	}
}

if( !function_exists('add_include_path') )
{
	/**
	 * @param string|array $entry
	 */
	function include_path_append($entry)
	{
		if( is_array($entry) )
		{
			$entry = implode(PATH_SEPARATOR,$entry);
		}
		if( !empty($entry) )
		{
			set_include_path( get_include_path().PATH_SEPARATOR.$entry );
		}
	}
}

if( !function_exists('autoload') )
{
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
}

if( !function_exists('loader_include') )
{
	/**
	 * @param string $path
	 * @return mixed
	 */
	function loader_include( $path )
	{
		return include( $path );
	}
}

if( !function_exists('loader_include_dg') )
{
	/**
	 * @param string|callable|null $path
	 * @return callable
	 */
	function loader_include_dg( $path=null )
	{
		if( null===$path )
		{
			$path = function()
			{
				return func_get_arg(0);
			};
		}
		elseif( is_string($path) )
		{
			$path = return_dg($path);
		}
		else
		{
			debug_enforce_type( is_string($path), 'callable' );
		}
		return function()use($path)
		{
			$args = func_get_args();
			return loader_include(
				call_user_func_array( $path, $args )
			);
		};
	}
}

if( !function_exists('loader_include_once') )
{
	/**
	 * @param string $path
	 * @return mixed
	 */
	function loader_include_once($path)
	{
		if( file_exists( $path.'.local' ) )
		{
			include_once( $path.'.local' );
		}
		return include_once( $path );
	}
}

if( !function_exists('loader_include_once_dg') )
{
	/**
	 * @param string|callable|null $path
	 * @return callable
	 */
	function loader_include_once_dg( $path=null )
	{
		if( null===$path )
		{
			$path = function()
			{
				return func_get_arg(0);
			};
		}
		elseif( is_string($path) )
		{
			$path = return_dg( $path );
		}
		else
		{
			debug_enforce_type( $path, 'callable' );
		}
		return function()use($path)
		{
			$args = func_get_args();
			return loader_include_once(
				call_user_func_array( $path, $args )
			);
		};
	}
}

if( !function_exists('loader_require') )
{
	/**
	 * @param string $path
	 * @return mixed
	 */
	function loader_require( $path )
	{
		if( file_exists( $path.'.local' ) )
		{
			require( $path.'.local' );
		}
		return require( $path );
	}
}

if( !function_exists('loader_require_dg') )
{
	/**
	 * @param string|callable|null $path
	 * @return callable
	 */
	function loader_require_dg( $path=null )
	{
		if( null===$path )
		{
			$path = function()
			{
				return func_get_arg(0);
			};
		}
		elseif( is_string($path) )
		{
			$path = return_dg($path);
		}
		else
		{
			debug_enforce_type( $path, 'callable' );
		}
		return function()use($path)
		{
			$args = func_get_args();
			return loader_require_dg(
				call_user_func_array( $path, $args )
			);
		};
	}
}

if( !function_exists('loader_require_once') )
{
	/**
	 * @param string|callable $path
	 * @return mixed
	 */
	function loader_require_once( $path )
	{
		if( file_exists( $path.'.local' ) )
		{
			require_once( $path.'.local' );
		}
		return require_once( $path );
	}
}

if( !function_exists('loader_require_once_dg') )
{
	/**
	 * @param string|callable|null $path
	 * @return callable
	 */
	function loader_require_once_dg( $path=null )
	{
		if( null===$path )
		{
			$path = function()
			{
				return func_get_arg(0);
			};
		}
		elseif( is_string($path) )
		{
			$path = return_dg( $path );
		}
		else
		{
			debug_enforce_type( $path, 'callable' );
		}
		return function()use($path)
		{
			$args = func_get_args();
			return loader_require_once(
				call_user_func_array( $path, $args )
			);
		};
	}
}

if( !function_exists('loader_include_get') )
{
	/**
	 * @return callable
	 */
	function loader_include_get()
	{
		defined('MUYO_INCLUDE_METHOD') or define('MUYO_INCLUDE_METHOD','require_once');
		switch( MUYO_INCLUDE_METHOD )
		{
			case 'include':
				$include = loader_include_dg();
			break;
			case 'include_once':
				$include = loader_include_once_dg();
			break;
			case 'require':
				$include = loader_require_dg();
			break;
			case 'require_once':
			default:
				$include = loader_require_once_dg();
			break;
		}
		return $include;
	}
}

if( !function_exists('loader_include_dir_recursive') )
{
	/**
	 * @param string $directory
	 * @return array
	 */
	function loader_include_dir_recursive($directory)
	{
		$array = scandir($directory);
		$include = loader_include_get();
		$ret = [];
		array_walk(
			$array,
			function($name)use($include,$directory,&$ret)
			{
				if( $name!=='.' && $name !=='..' )
				{
					$nameLen = strlen($name);
					if( $nameLen>4 && $name[$nameLen-4]==='.' && $name[$nameLen-3]==='p' && $name[$nameLen-2]==='h' && $name[$nameLen-1]==='p' && is_file($directory.DIRECTORY_SEPARATOR.$name) )
					{
						$ret[$name] = $include( $directory.DIRECTORY_SEPARATOR.$name );
					}
					elseif( is_dir($directory.DIRECTORY_SEPARATOR.$name) && is_file($directory.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'_.php') )
					{
						$ret[$name] = $include( $directory.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'_.php' );
					}
				}
			}
		);
		return $ret;
	}
}