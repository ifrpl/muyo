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
