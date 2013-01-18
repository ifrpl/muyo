<?php

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
 * @param      $from
 * @param      $to
 * @param bool $to_as_root
 *
 * @return string
 */
function ifr_path_rel($from, $to, $to_as_root = false)
{
	$from = realpath($from);
	$to = realpath($to);

	debug_assert( is_string($from) && is_string($to) );

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
			if ( $to[$j] === DIRECTORY_SEPARATOR )
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

	if ( !$to[$to_cnt-1] != DIRECTORY_SEPARATOR )
	{
		$to .= DIRECTORY_SEPARATOR;
		$to_cnt++;
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
		array_reverse(explode('/',trim($path,'/'))),
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
	if(str_endswith($path,'/.') || str_endswith($path,'/..'))
	{
	}
	else
	{
		@array_map('rrmdir',@glob($path.'/*'));
		@array_map('rrmdir',@glob($path.'/.*'));
		@unlink($path);
		@rmdir($path);
	}
}