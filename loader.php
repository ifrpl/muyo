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
				$include = function($path){ include($path); };
			break;
			case 'include_once':
				$include = function($path){ include_once($path); };
			break;
			case 'require':
				$include = function($path){ require($path); };
			break;
			case 'require_once':
			default:
				$include = function($path){ require_once($path); };
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