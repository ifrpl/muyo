<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/** @var callable $logger */
$logger = null;


class Logger
{
	static public function debug($message)
	{
		return logger_log($message, LOG_DEBUG);
	}

	static public function info($message)
	{
		return logger_log($message, LOG_INFO);
	}

	static public function warn($message)
	{
		return logger_log($message, LOG_WARNING);
	}

	static public function error($message)
	{
		return logger_log($message, LOG_ERR);
	}
}

/**
 * @param string|\Exception $message
 * @param int $level
 * @return null
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

		if( in_array(getCurrentEnv(), array('production','testing') ) )
		{
			mail( 'atrium-dev@ifresearch.org', 'Exception on '.$_SERVER[ 'HTTP_HOST' ], $message );
		}
	}

	$logger( $message, $level );
	return null;
}

/**
 * @return callable|Zend_Log
 */
function logger_get()
{
	global $logger;
	return $logger;
}

/**
 * @param callable|null $val that takes ($message, $level)
 */
function logger_set( $val=null )
{
	global $logger;

	if( null !== $val )
	{
		debug_assert(is_callable($val), $val);
	}
	else
	{
		$val = logger_default();
	}

	$logger = $val;
}

/**
 * Returns default logger implementation
 *
 * @param $eol
 * @return callable
 */
function logger_default($eol="\n")
{
	return function( $message, $level=LOG_INFO )use($eol)
	{
		$msg = '';
		$now = now();
		$level = log_level_str($level);

		if(!is_array($message)){
			$message = explode($eol, $message);
		}

		for($i=0; $i<count($message); $i++)
		{
			$msg .= sprintf("[%s] [%7s] %s", $now, $level, $message[$i]);
			if($i<count($message)-1)
			{
				$msg .= PHP_EOL;
			}
		}

		printrlog($msg);
	};
}

// Set default logger
logger_set();

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