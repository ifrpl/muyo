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

	if($message instanceof \Exception)
	{ /* @var Exception $message */
		$tmp = 'Exception: '.get_class($message) . ' :: ' . $message->getMessage();
		$tmp.= ' | '.$message->getFile().':'.$message->getLine();
		$tmp.= "\nBacktrace:\n".$message->getTraceAsString();

		if(isset($_SERVER['REQUEST_URI']))
		{
			$tmp.= "\nREQUEST:\n".$_SERVER['REQUEST_URI'];
		}
		if(isset($_POST))
		{
			$tmp.= "\nPARAMS POST:\n".json_encode($_POST);
		}
		if(isset($_SERVER))
		{
			$tmp.= "\nSERVER:\n".base64_encode(json_encode($_SERVER));
		}

		$message = $tmp;
		if(defined('APPLICATION_ENV') && (APPLICATION_ENV == 'production' || APPLICATION_ENV == 'testing'))
		{
			mail('atrium-dev@ifresearch.org', 'Exception on '.$_SERVER['HTTP_HOST'], $message);
		}
	}

	if( null !== $logger )
	{
		$logger($message, $level);
	}
	else
	{
		$msg = '['.now().']';
		$msg .= ' ['.$level.']';
		$msg .= ' '.$message."\n";
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
