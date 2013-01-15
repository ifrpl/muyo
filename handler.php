<?php

namespace ifr\main\handler;
use \ifr\main\debug as debug;
use \ifr\main\log as log;

$error_handlers = array(
	'assert'    => array(),
	'php_error'       => array(),
	'exception' => array(),
);

function init()
{
	global $error_handlers;

	debug();
	log();

	$env = getCurrentEnv();

	/**
	 * Assertion handlers
	 */
	assert_options(ASSERT_CALLBACK, function() use ($error_handlers){
		if(count($error_handlers['assert']))
		{
			$arg_parser = function($script, $line, $message)
			{
				return array(
					'message' => $message,
					'script' => $script,
					'line' => $line,
					'trace' => array(),
					'type' => 'assertion',
					'other' => array()
				);
			};
			$args = call_user_func_array($arg_parser, func_get_args());
			foreach($error_handlers['assert'] as $handler)
			{

				call_user_func_array($handler, $args);
			}
		}
	});
	assert_options(ASSERT_BAIL, $env!='production');
	assert_options(ASSERT_QUIET_EVAL, $env!='production');
	assert_options(ASSERT_ACTIVE, $env!='production');
	assert_options(ASSERT_WARNING, $env=='production');

	/**
	 * PHP Error handlers
	 */
	set_error_handler(function() use ($error_handlers){
		if(count($error_handlers['php_error']))
		{
			$arg_parser = function($number, $message, $script, $line)
			{
				return array(
					'message' => $message,
					'script' => $script,
					'line' => $line,
					'trace' => array(),
					'type' => 'php_error',
					'other' => array(
						'php_error' => $number
					)
				);
			};
			$args = call_user_func_array($arg_parser, func_get_args());
			foreach($error_handlers['php_error'] as $handler)
			{
				call_user_func_array($handler, $args);
			}
		}
	});

	/**
	 * Exception handlers
	 */
	set_exception_handler(function() use ($error_handlers){
		if(count($error_handlers['exception']))
		{
			$arg_parser = function($e)
			{
				return array(
					'message' => $e->getMessage(),
					'script' => $e->getFile(),
					'line' => $e->getLine(),
					'trace' => $e->getTrace(),
					'type' => 'exception',
					'other' => array(
						'exception' => $e
					)
				);
			};
			$args = call_user_func_array($arg_parser, func_get_args());
			foreach($error_handlers['exception'] as $handler)
			{
				call_user_func_array($handler, $args);
			}
		}
	});
}

/**
 * @param string $nameHandler
 * @param null|callable $handler
 * @param null|string $typeHandler
 */
function register($nameHandler, $handler = null, $typeHandler = null)
{
	global $error_handlers;

	if($typeHandler)
	{
		if(array_key_exists($typeHandler, $error_handlers))
		{
			$error_handlers[$typeHandler][$nameHandler] = $handler;
		}
	}
	else
	{
		foreach($error_handlers as $typeHandler => $h)
		{
			$error_handlers[$typeHandler][$nameHandler] = $handler;
		}
	}
}

function debug($handler = null)
{
	$defaultHandler = function($message, $script, $line, $trace, $type, $other){

		debug\writeln("Error {$type}");

		switch($type)
		{
			case "exception":
				$class = get_class($other['exception']);
				debug\writeln("Unhandled `{$class}`({$other['exception']->getCode()}):");
				break;
			case "php_error":
				debug\writeln("PHP Error: {$other['php_error']}");
				break;
			case "assertion":
				debug\writeln("Assertion Failed:");
				break;
		}

		debug\writeln("{$script}:{$line} :: {$message}\n");

		debug\writeln("Backtrace:");
		if($trace)
		{
			debug\backtrace_print(0,$trace);
		}
		else
		{
			debug\backtrace_print(1);
		}
	};

	$default_handlers = array(
		'development' => $defaultHandler,
		'testing'     => $defaultHandler,
		'production'  => function(){
			return false;
		}
	);

	$env = getCurrentEnv();

	if( null == $handler )
	{
		$handler = $default_handlers[ $env ];
	}

	register('debug', $handler);
}

/**
 * @param $message
 * @param $script
 * @param $line
 * @param $trace
 * @param $type
 * @param $other
 */
function logAction( $message, $script, $line, $trace, $type, $other )
{
	$msg = '['.$type.']';

	switch($type)
	{
		case "exception":
			$class = get_class($other['exception']);
			$msg .= ' ['.$class.']';
			break;
	}

	if(LOGGER == 'syslog')
	{
		$sep = ' - ';
	}
	else
	{
		$sep = "\n";
	}

	$msg .= ' '.$message;
	$msg .= " - {$script}:{$line}";

	if(isset($_SERVER['REQUEST_URI']))
	{
		$msg .= $sep."REQUEST: ".$_SERVER['REQUEST_URI'];
	}
	if($trace)
	{
		$msg .= $sep."TRACE:".$sep;
		$msg .= debug\backtrace_string(0, $trace);
	}
	if(isset($_REQUEST) && count($_REQUEST))
	{
		$msg .= $sep."PARAMS: ";
		$msg .= json_encode($_REQUEST);
	}
	$msg .= $sep."SERVER: ";
	$msg .= json_encode($_SERVER);

	log\log($msg, LOG_ERR);
}

/**
 * @param handler
 */
function log($handler = null)
{
	$default_handlers = array(
//		'development' => function(){},
		'development'     => '\ifr\main\handler\logAction',
		'testing'     => '\ifr\main\handler\logAction',
		'production'  => '\ifr\main\handler\logAction'
	);

	$env = getCurrentEnv();

	if( null == $handler )
	{
		$handler = $default_handlers[ $env ];
	}

	register('log', $handler);
}