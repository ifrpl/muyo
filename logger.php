<?php

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
		$tmp = 'Exception: '.get_class($message);
		$tmp.= ' | '.$message->getFile().':'.$message->getLine();
		$tmp.= "\nBacktrace:\n".$message->getTraceAsString();
		$message = $tmp;
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
