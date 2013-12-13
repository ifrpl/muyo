<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/** @var callable $logger */
$logger = null;

/**
 * @param string|\Exception $message
 * @param int $level
 *
 * @see Zend_Log::WARN
 * @see LOG_WARNING
 *
 * @throws \Exception
 */
function logger_log($message, $level = LOG_INFO)
{
	global $logger;
	$eol = "\n";
	$indent = "\t";

	if( $message instanceof \Exception )
	{
		$message = exception_str( $message, $eol );
		if( isset( $_SERVER[ 'REQUEST_URI' ] ) && !empty( $_SERVER[ 'REQUEST_URI' ] ) )
		{
			$message .=
				str_indent( "REQUEST:".$eol.
					str_indent( $_SERVER[ 'REQUEST_URI' ], 1, $indent )
				, 1, $indent ).$eol
			;
		}
		if( isset( $_POST ) && !empty( $_POST ) )
		{
			$message .=
				str_indent( "PARAMS POST:".$eol.
					str_indent( json_encode( $_POST ), 1, $indent )
				, 1, $indent ).$eol
			;
		}
		if( isset( $_SERVER ) && !empty( $_SERVER ) )
		{
			$message .=
				str_indent( "SERVER:".$eol.
					str_indent( json_encode( $_SERVER ), 1, $indent )
				, 1, $indent ).$eol
			;
		}

		if( defined( 'APPLICATION_ENV' ) && ( APPLICATION_ENV=='production' || APPLICATION_ENV=='testing' ) )
		{
			mail( 'atrium-dev@ifresearch.org', 'Exception on '.$_SERVER[ 'HTTP_HOST' ], $message );
		}
	}

	if( null !== $logger )
	{
		$logger($message, $level);
	}
	else
	{
		$msg = '';
		$now = now();
		$level = log_level_str($level);
		foreach( explode( $eol, $message ) as $line )
		{
			$msg .= "[$now] [$level] $line".$eol;
		}

		printrlog($msg);
	}
}

/**
 * @param callable $val that takes ($message, $level)
 */
function logger_set($val)
{
	global $logger;

	if( debug_assert(is_callable($val), $val) )
	{
		$logger = $val;
	}
}

/**
 * @param string $file File name to check fo rotation
 * @param float $maxSize Max file size in MB unit
 */
function logger_rotate($file, $maxSize)
{
	$size = filesize($file) / 1024 / 1024; // size in MB

	if($size > $maxSize)
	{
		$files = glob($file.'*');
		array_shift($files);

		$count = count($files) + 1;
		foreach(array_reverse($files) as $oldFile)
		{
			exec("mv {$oldFile} {$file}.{$count}.gz");
			$count--;
		}

		exec("gzip -c {$file} > {$file}.{$count}.gz");
		exec("> {$file}");
	}

}

/**
 * @param int $level
 * @return string
 */
function log_level_str($level)
{
	$map = array(
		LOG_EMERG => 'EMERG',
		LOG_ALERT => 'ALERT',
		LOG_CRIT => 'CRIT',
		LOG_ERR => 'ERR',
		LOG_WARNING => 'WARNING',
		LOG_NOTICE => 'NOTICE',
		LOG_INFO => 'INFO',
		LOG_DEBUG => 'DEBUG',
	);
	if( debug_assert( array_key_exists( $level, $map ), 'Unknown log level' ) )
	{
		$level = $map[ $level ];
	}
	return $level;
}