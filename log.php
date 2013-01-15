<?php

namespace ifr\main\log;

/**
 * @param string|\Exception $message
 * @param int $level
 *
 * @throws \Exception
 */
function log($message, $level = LOG_INFO)
{
	if($message instanceof \Exception)
	{
		$message = 'Exception: '.get_class($message);
		$message .= ' | '.$message->getFile().':'.$message->getLine();
		$message .= "\nBacktrace:\n".$message->getTraceAsString();
	}

	$msg = '['.now().']';
	$msg .= ' ['.$level.']';
	$msg .= ' '.$message."\n";

	if(!defined('LOGGER'))
	{
		throw new \Exception('Logger not initialized');
	}

	if(LOGGER == 'syslog')
	{
		syslog($level, $message);
	}
	else
	{
		$name = LOGGER_NAME;
		file_put_contents(LOGGER."/{$name}.log", $msg, FILE_APPEND);

		if($level <= LOG_NOTICE)
		{
			file_put_contents(LOGGER."/{$name}.warn.log", $msg, FILE_APPEND);
		}

		if($level <= LOG_ERR)
		{
			file_put_contents(LOGGER."/{$name}.error.log", $msg, FILE_APPEND);
		}

		if($level <= LOG_INFO)
		{
			file_put_contents(LOGGER."/{$name}.info.log", $msg, FILE_APPEND);
		}
	}
}

/**
 * @see openlog()
 *
 * @param string $ident
 * @param string $log Value 'syslog' open login to syslog. Otherwise path to log dir
 * @param int $option
 * @param int $facility
 *
 * @return bool
 */
function start($ident, $log = 'syslog', $option = null, $facility = LOG_SYSLOG)
{
	if(!$option)
	{
		$option = LOG_PID;
	}

	define('LOGGER_NAME', $ident);
	if($log == 'syslog')
	{
		$result = openlog($ident, $option, $facility);
		if($result)
		{
			define('LOGGER', 'syslog');
			return true;
		}
	}

	if($log != 'syslog')
	{
		define('LOGGER', $log);
	}
	else
	{
		define('LOGGER', ROOT_PATH.'/tmp');
	}

	return true;
}

/**
 * @see closelog()
 */
function stop()
{
	closelog();
}