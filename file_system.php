<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/**
 * @param string $dir
 *
 * @return array
 */
function ifr_dir_flatten($dir)
{
	$ret = array();
	foreach ( scandir($dir) as $fs_entity )
	{
		if ( $fs_entity == '.' || $fs_entity == '..' )
			continue;

		$fs_entity = $dir . DIRECTORY_SEPARATOR . $fs_entity;

		if ( is_dir($fs_entity) )
		{
			$ret = array_merge($ret, ifr_dir_flatten($fs_entity));
		}
		else
		{
			$ret []= $fs_entity;
		}
	}
	return $ret;
}

/**
 * @param string $from absolute path from
 * @param string $to absolute path to
 * @param bool $to_as_root
 *
 * @return string
 */
function ifr_path_rel($from, $to, $to_as_root = false)
{
	$from_cnt = strlen($from);
	$to_cnt = strlen($to);
	$min_cnt = min($from_cnt,$to_cnt);

	if ( $to_as_root )
		debug_assert( $from_cnt > $to_cnt );

	// traverse through equal path
	for( $i = 0; $i < $min_cnt; $i++)
	{
		if ( $from[$i] !== $to[$i] )
			break;
	}

	// make sure we're on last separator
	while( $i > 0 )
	{
		if ( $from[$i] === DIRECTORY_SEPARATOR )
		{
			if ( !ifr_escaped(substr($from, 0, $i+1)) ) // i hope that optimizer will work there
			{
				break;
			}
		}
		$i--;
	}

	$from = substr($from, $i);
	$from_cnt = $from_cnt - $i;
	$to = substr($to, $i);
	$to_cnt = $to_cnt - $i;

	for ( $i = 1; $i < $to_cnt; $i++ )
	{
		for ( $j = $i+2; $j < $to_cnt; $j++ )
		{
			if ( isset($to[$j]) && $to[$j] === DIRECTORY_SEPARATOR )
			{
				$to = substr($to, 0, $i-1).'..'.substr($to, $j, $to_cnt);
				$to_cnt -= $j-$i+1;
				$to_cnt +=2;
				$i += 2;
				break;
			}
			else if ( $j === $to_cnt-1 )
			{
				$to = substr($to, 0, $i-1);
			}
		}
	}

	if ( isset($to[$to_cnt-1]) && $to[$to_cnt-1] != DIRECTORY_SEPARATOR )
	{
		$to .= DIRECTORY_SEPARATOR;
	}

	if ( !$to_as_root )
	{
		$from = substr($from, 1);
	}

	$ret = $to.$from;

	return $ret;
}

/**
 * @param string $path
 * @param null $data
 *
 * @return mixed
 */
function path2array($path,$data=null)
{
	return array_reduce(
		array_reverse(explode(DIRECTORY_SEPARATOR,trim($path,DIRECTORY_SEPARATOR))),
		function($sum,$sub)
		{
			return array($sub=>$sum);
		},
		$data
	);
}

/**
 * @param string $path
 */
function rrmdir($path)
{
	if( !str_endswith($path,'/.') && !str_endswith($path,'/..') )
	{
		@array_map('rrmdir',@glob($path.'/*'));
		@array_map('rrmdir',@glob($path.'/.*'));
		@unlink($path);
		@rmdir($path);
	}
}

function rmkdir($path)
{
	$blocks = explode( DIRECTORY_SEPARATOR, $path );
	$assembled = '';
	foreach( $blocks as $block ) //FIXME: quoted paths
	{
		$assembled .= $block;
		if( !empty($block) && !is_dir($assembled) )
		{
			mkdir($assembled);
		}
		$assembled .= DIRECTORY_SEPARATOR;
	}
}

/**
 * @param string $path
 * @return string
 */
function trim_application_path($path)
{
	if( str_startswith($path, ROOT_PATH) )
	{
		$path = substr($path,strlen(ROOT_PATH)+1);
		if( PATH_SEPARATOR === $path[0] )
		{
			$path = substr($path,1);
		}
		$path = './'.$path;
	}
	return $path;
}

/**
 * @param string $suffix
 * @param string $prefix
 * @param string|null $dir
 * @param int $tries
 * @return string path to new (existing) temporary file
 */
function tempfile_str($suffix = '',$prefix = '',$dir = null,$tries = 5)
{
	if( !debug_assert( is_string($suffix), "Invalid suffix '$suffix'" ) )
	{
		$suffix = '';
	}
	if( !debug_assert( is_string($prefix), "Invalid prefix '$prefix'" ) )
	{
		$prefix = '';
	}
	if( is_null($dir) )
	{
		$dir = sys_get_temp_dir();
	}
	else
	{
		if( !debug_assert( is_dir($dir), "Invalid directory '$dir'" ) )
		{
			$dir = sys_get_temp_dir();
		}
	}
	if( !str_endswith($dir,DIRECTORY_SEPARATOR) )
	{
		$dir .= DIRECTORY_SEPARATOR;
	}

	for ($i = 0; $i < $tries; $i++)
	{
		$path = $dir.$prefix.str_ascii7_prand(20,'ctype_alnum').$suffix;
		$handle = @fopen($path,'xb');
		if( false !== $handle )
		{
			debug_assert( true === fclose($handle) );
			return $path;
		}
	}
	debug_enforce( false, "Failed to create temporary file '$tries' times in a row." );
	return false;
}

/**
 * @param string $path
 * @throws ErrorException
 */
function ensure_dir_exists($path)
{
	if( !@mkdir($path) )
	{
		$error = error_get_last();
		if( $error['type'] !== 2 )
		{
			throw new ErrorException($error['message'],$error['type'],1,$error['file'],$error['line']);
		}
	}
}

function ensure_dir_exists_dg( $path_getter )
{
	$path_getter = callablize( $path_getter );
	if( is_callable($path_getter) )
	{
		return function()use( $path_getter )
		{
			$args = func_get_args();
			$path = call_user_func_array( $path_getter, $args );
			ensure_dir_exists( $path );
		};
	}
	else
	{
		return function()use( $path_getter )
		{
			ensure_dir_exists( $path_getter );
		};
	}
}

/**
 * @param     $pattern
 * @param int $flags
 * @return array
 */
function glob_match($pattern, $flags=0)
{
	$regex=str_glob2regexp( $pattern, $flags & GLOB_BRACE );
	return array_map_val( glob( $pattern, $flags ), function($path)use($regex,$pattern)
	{
		debug_assert(
			preg_match( $regex, $path, $matches ),
			"Cannot match '$regex' on '$path' ( generated by '$pattern' )"
		);
		return $matches;
	} );
}

/**
 * @param string|callable $filename_getter
 * @param mixed|callable $data_getter
 * @param int|callable $flags_getter
 * @param resource|callable|null $context_getter
 * @return callable
 */
function file_put_contents_dg( $filename_getter, $data_getter, $flags_getter=0, $context_getter=null )
{
	$filename_getter = callablize( $filename_getter );
	$data_getter = callablize( $data_getter );
	$flags_getter = callablize( $flags_getter );
	if( $context_getter!==null )
	{
		$resource_getter = callablize( $context_getter );
		return function()use( $filename_getter, $data_getter, $flags_getter, $resource_getter )
		{
			$args = func_get_args();
			return file_put_contents(
				call_user_func_array( $filename_getter, $args ),
				call_user_func_array( $data_getter, $args ),
				call_user_func_array( $flags_getter, $args ),
				call_user_func_array( $resource_getter, $args )
			);
		};
	}
	else
	{
		return function()use( $filename_getter, $data_getter, $flags_getter )
		{
			$args = func_get_args();
			return file_put_contents(
				call_user_func_array( $filename_getter, $args ),
				call_user_func_array( $data_getter, $args ),
				call_user_func_array( $flags_getter, $args )
			);
		};
	}
}

/**
 * @param string|callable|null $filename_getter
 * @param resource|callable|null $context_getter
 * @return callable
 */
function unlink_dg( $filename_getter, $context_getter=null )
{
	$filename_getter = callablize( $filename_getter );
	if( $context_getter!==null )
	{
		$context_getter = callablize( $context_getter );
		return function()use( $filename_getter, $context_getter )
		{
			$args = func_get_args();
			$filename = call_user_func_array( $filename_getter, $args );
			$context = call_user_func_array( $context_getter, $args );
			debug_enforce(
				unlink( $filename, $context ),
				"Could not unlink '{$filename}'"
			);
		};
	}
	else
	{
		return function()use( $filename_getter )
		{
			$args = func_get_args();
			$filename = call_user_func_array( $filename_getter, $args );
			debug_enforce(
				unlink( $filename ),
				"Could not unlink '{$filename}'"
			);
		};
	}
}

/**
 * @param string|callable $directory_getter
 * @return callable
 */
function chdir_dg( $directory_getter )
{
	$directory_getter = callablize( $directory_getter );
	return function()use( $directory_getter )
	{
		$args = func_get_args();
		$directory = call_user_func_array( $directory_getter, $args );
		debug_enforce( chdir($directory), "Could not change working directory to '{$directory}'" );
	};
}

/**
 * @param string $backupFile
 * @param string $path
 * @return bool
 */
function tar_contains( $backupFile, $path )
{
	$output = tar_ls( $backupFile );
	return array_some( $output, function( $line )use( $path )
	{
		return str_startswith( $line, $path );
	});
}

/**
 * @param string $backupFile
 * @return array
 */
function tar_ls( $backupFile )
{
	proc_exec( "tar -ztvf {$backupFile} | awk '{print $6}'", $output );
	return $output;
}