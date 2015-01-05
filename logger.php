<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/** @var callable $logger */
$logger = null;


class Logger
{
	static public function dump($obj, $message = null, $logLevel = LOG_DEBUG)
	{
		if(null == $message)
		{
			$message = buildIdFromCallstack(1);
		}

		return self::_dump($obj, $message, $logLevel);
	}

	static public function dumpToFile($obj, $fileName = '')
	{
		$id = buildIdFromCallstack(1);

		$outputDirPath = ROOT_PATH . DIRECTORY_SEPARATOR . 'data/tmp/dump/' . $id;
		if(!file_exists($outputDirPath))
		{
			mkdir($outputDirPath, 0777, true);
		}

		if(!empty($fileName))
		{
			$fileName .= '-';
		}

		$fileName .= IFR_Main_Time::udate('Ymd-His-u') . '.txt';

		$dumpFilePath = $outputDirPath. DIRECTORY_SEPARATOR . $fileName;

		$outputFile = fopen($dumpFilePath, 'wt');
		self::_dump($obj, '', -1, $outputFile);
		fclose($outputFile);

		return $dumpFilePath;
	}

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

	public static function notice($message)
	{
		return logger_log($message, LOG_NOTICE);
	}

	private static function _dump($obj, $message = null, $logLevel = LOG_DEBUG, $outputFile = null)
	{
		if(is_array($obj))
		{
			$collection = array_reduce_val(
				$obj,
				function($startValue, $val, $key){
					return $startValue || ($val instanceof Lib_Model);
				},
				false
			);

			if($collection)
			{
				$message .= ' [collection]';

				array_each(
					$obj,
					function($value, $key) use($message, $logLevel, $outputFile){
						self::_dump($value, $message . "[$key]", $logLevel, $outputFile);
					}
				);

				return;
			}

		}

		if($obj instanceof Lib_Model)
		{
			$message .= sprintf(' %s->toArray()', get_class($obj));

			/* @var Lib_Model $obj */
			$obj = $obj->toArray();
		}

		$dump = var_export($obj, true);
		if(null != $outputFile)
		{
			fwrite($outputFile, $message . ': ' . $dump);
		}
		else
		{
			logger_log($message . ': ' . $dump, $logLevel);
		}
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

	debug_assert(is_callable($logger), "Logger is not callable. Type: " . gettype($logger));
	
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

		if( !is_array($message) )
		{
			$message = explode($eol, $message);
		}

		for($i = 0; $i<count($message); $i++)
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

/**
 * @param mixed $var
 * @param int $level
 * @return null
 */
function logger_log_compact($var,$level=LOG_DEBUG)
{
	return logger_log( var_dump_human_compact($var), $level );
}

/**
 * @param mixed $var
 * @param int $level
 * @return null
 */
function logger_log_full( $var, $level=LOG_DEBUG )
{
	return logger_log( var_dump_human_full($var), $level );
}