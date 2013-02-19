<?php

/**
 * @return bool
 */
function debug_allow()
{
	if(!function_exists('is_debug_host'))
	{
		/**
		 * @return bool
		 */
		function is_debug_host()
		{
			$allowedSubNet = array(
				'127.',
				'10.10.',
				'192.168.'
			);

			$allowedHosts = array(
				'10.0.2.2',
				'89.191.162.220', //Lukasz home
				'87.206.45.163',
				'84.10.100.73',
				'89.69.131.15' //IFResearch Chello
			);

			if((isCLI() && defined('APPLICATION_ENV') && APPLICATION_ENV !== 'production'))
			{
				return true;
			}
			elseif(!isCLI() && isset($_SERVER['REMOTE_ADDR']))
			{
				foreach($allowedSubNet as $subNet)
				{
					if(strpos($_SERVER['REMOTE_ADDR'], $subNet) === 0)
					{
						return true;
					}
				}
				if(in_array($_SERVER['REMOTE_ADDR'], $allowedHosts))
				{
					return true;
				}
			}
			return false;
		}
	}
	$env = getCurrentEnv();

	if(
		!is_debug_host() ||
		($env === 'production' && (!isset($_COOKIE['ifrShowDebug']) || $_COOKIE['ifrShowDebug'] !== 'iLuv2ki11BugsBunny!'))
	) return false;

	return true;
}

/**
 * @param mixed $tab
 * @param ...
 */
function printr($tab)
{
	if(!debug_allow()) return null;

	if( func_num_args() > 1 )
	{
		$tab = func_get_args();
	}
	$dbg = debug_backtrace();
	$cli = isCLI();
	if(!$cli)
	{
		echo "<pre style='background-color: #efefef; border: 1px solid #aaaaaa; color:#000;'>";
		$f = "{$dbg[0]['file']}:{$dbg[0]['line']}";
		echo "<div style='font-weight: bold; background-color: #FFF15F; border-bottom: 1px solid #aaaaaa;'><a href='http://localhost:8091?message=$f'>$f</a></div>";
	}
	$tmp = print_r($tab,true);

	if($cli === 2)
	{
		$tmp = str_replace("\n",'',$tmp);
	}
	echo $tmp;
	if(!$cli)
	{
		echo "</pre>\n";
	}
	else
	{
		echo "\n";
	}
}

/**
 * @param $tab
 */
function printrlog($tab)
{
	$dbg = debug_backtrace();
	$msg = "###################################################";
	$msg .= "\n    {$dbg[0]['file']}:{$dbg[0]['line']}\n\n";
	$msg .= print_r($tab,true);
	$msg .= "\n";

	$logdir = ROOT_PATH.'/tmp';
	if( !is_dir($logdir) )
	{
		mkdir($logdir, 0777, true);
	}
	file_put_contents(ROOT_PATH.'/tmp/log',$msg,FILE_APPEND);
	writeln($tab);
}

/**
 * @param string $str
 * @param int    $count
 * @param bool   $do_print
 *
 * @return string|null
 */
function printn($str='',$count=1,$do_print=true)
{
	if(!debug_allow()) return null;

	if(is_bool($count))
	{
		if(!is_bool($do_print))
		{
			$tmp = $do_print;
		}

		$do_print = $count;

		$count = isset($tmp)?$tmp:1;
	}
	$str.=str_pad('', $count, "\n");

	$do_print and print($str);

	return $str;
}

/**
 * @param $tab
 */
function printfb($tab)
{
	if(!debug_allow()) return;

	header('debug: '.$tab);
}

/**
 * @param int $ignore_depth
 * @return array
 */
function backtrace($ignore_depth = 0)
{
	$ignore_depth++;
	$ret = array_map(
		function($row){unset($row['object']); return $row;},
		debug_backtrace()
	);
	return array_splice($ret, $ignore_depth);
}

/**
 * Returns multi-line human-readable representation of a variable.
 * @param mixed $var
 *
 * @return string
 */
function var_dump_human_full($var)
{
	ob_start();
	print_r($var);
	return ob_get_clean();
}

/**
 * Returns single-line human-readable representation of a variable.
 * @param mixed $var
 * @param mixed $key internal (recursive) use only, this is a key matched with array value
 * @return string
 */
function var_dump_human_compact($var, $key = null)
{
	$ret = '';
	if ( !is_null($key) && !is_numeric($key) )
	{
		$ret .= var_dump_human_compact($key).'=>';
	}
	if ( is_array($var) )
	{
		if( $key && array_key_is_reference($var, $key) )
		{
			$ret .= '&'.gettype($var);
		}
		else
		{
			$tmp = array();
			foreach( $var as $k=>$v )
			{

				$tmp []= var_dump_human_compact($v, $k);
			}
			$ret .= '['.implode(',',$tmp).']';
		}
	}
	elseif ( is_null($var) )
	{
		$ret .= 'NULL';
	}
	elseif ( false === $var )
	{
		$ret .= 'false';
	}
	elseif ( true === $var )
	{
		$ret .= 'true';
	}
	elseif ( is_object($var) )
	{
		$class = get_class($var);
		if ( $class === 'Object' )
		{
			$ret .= var_dump_human_compact((array)$var);
		}
		else
		{
			$ret .= get_class($var);
		}
	}
	elseif ( is_string($var) )
	{
		$ret .= '"'.$var.'"';
	}
	else
	{
		$ret .= $var;
	}
	return $ret;
}

/**
 * @param int $ignore_depth stack frames to ignore
 * @param null|mixed $backtrace backtrace array to print
 */
function backtrace_print($ignore_depth = 0, $backtrace = null)
{
	$text = backtrace_string($ignore_depth+1, $backtrace);
	writeln($text);
}

/**
 * @return string
 */
function backtrace_string($ignore_depth = 0, $backtrace = null)
{
	if( is_null($backtrace) )
	{
		$backtrace = backtrace($ignore_depth+1);
	}

	$max_len_file = 0;
	foreach( $backtrace as $val )
	{
		if ( isset($val['file']) )
		{
			$len = strlen($val['file']);
			$max_len_file = max($max_len_file,$len);
		}
	}
	$max_len_file += 2;

	$ret = '';
	foreach( $backtrace as $val )
	{
		$file = isset($val['file']) ? $val['file'] : '';
		$line = isset($val['line']) ? $val['line'] : '';
		$function = isset($val['function']) ? $val['function'] : '';
		$args = isset($val['args']) ? var_dump_human_compact($val['args']) : '';

		$append = !empty($file) ? $file : '???';

		if ( !empty($file) && !empty($line) )
		{
			$append .= ":$line";
		}
		if ( strlen($append) < $max_len_file )
		{
			$append = str_pad($append, $max_len_file+1);
		}
		$append .= "  $function($args)\n";
		$ret .= $append;
	}
	debug_assert(is_string($ret));
	return $ret;
}

/**
 * @param mixed $tab
 * @param mixed ...
 */
function debug($tab)
{
	if(!debug_allow()) return;

	$trace = backtrace(1);
	if( !isCLI() )
	{
		write("<pre style='background-color: #efefef; border: 1px solid #aaaaaa; color:#000;'>");

		$traceFile = backtrace();
		$f = "{$traceFile[0]['file']}:{$traceFile[0]['line']}";
		write("<div style='font-weight: bold; background-color: #FFF15F; border-bottom: 1px solid #aaaaaa;'><a href='http://localhost:8091?message=$f'>$f</a></div>");

		write("<hr>");
		write(call_user_func_array('var_dump_human_full', func_get_args()));
		write("<hr>");
		backtrace_print(0, $trace);
		write("</pre>");
	}
	else
	{
		write("\n===== Debug  Variable =====\n");
		write(call_user_func_array('var_dump_human_full', func_get_args()));
		write("\n======= Debug Break =======\n");
		backtrace_print(0, $trace);
		write("\n======= Exiting... ========\n");
	}
	exit();
}

if(!function_exists('get_call_stack'))
{
	/**
	 * @return array|null
	 */
	function get_call_stack()
	{
		$dbg = debug_backtrace();

		array_shift($dbg);
		$calls = array();
		foreach($dbg as $b)
		{
			array_push($calls, $b['file'].':'.$b['line'].' | '.$b['class'].'::'.$b['function']);
		}
		return $calls;
	}
}

if( class_exists('Zend_Log') )
{
	debug_assert(function() {
		foreach(array(
			LOG_EMERG   => Zend_Log::EMERG,
			LOG_ALERT   => Zend_Log::ALERT,
			LOG_CRIT    => Zend_Log::CRIT,
			LOG_ERR     => Zend_Log::ERR,
			LOG_WARNING => Zend_Log::WARN,
			LOG_NOTICE  => Zend_Log::NOTICE,
			LOG_INFO    => Zend_Log::INFO,
			LOG_DEBUG   => Zend_Log::DEBUG
		) as $k => $v)
		{
			if( $k != $v )
			{
				return false;
			}
		}
		return true;
	}, 'One of log levels differ. Update the library.');
}

/**
 * @param      $assertion
 * @param null $message
 *
 * @return bool
 */
function debug_assert($assertion, $message = null)
{
	if( is_callable($assertion) )
	{
		$assertion = $assertion();
	}

	if( version_compare(PHP_VERSION, '5.4.8', '>=') )
	{
		return assert($assertion, var_dump_human_compact($message));
	}
	else
	{
		if( is_string($assertion) && debug_allow() )
		{
			$assertion = eval($assertion);
		}
		if( !$assertion )
		{
			$handler = assert_options(ASSERT_CALLBACK);

			if( null !== $handler )
			{ /** @var Callable $handler */
				$trace = backtrace(1);
				$file = isset($trace['file']) ? $trace['file'] : '';
				$line = isset($trace['line']) ? $trace['line'] : '';
				$handler($file, $line, $message);
			}
		}
		return $assertion;
	}
}

/**
 * @param mixed $text variables to print
 * @param mixed ... more variables to print
 */
function write($text /**, $more_text **/)
{
	if(!debug_allow()) return null;

	ob_start();
	echo implode(',',array_map(function($arg)
	{
		if ( !is_string($arg) )
		{
			$arg = var_dump_human_compact($arg);
		}

		if(!isCLI())
		{
			$arg = str_replace("\n", "<br/>", $arg);
		}

		echo($arg);
	},func_get_args()));
	ob_end_flush();
}

/**
 * @param mixed $text variables to print
 * @param mixed ... more variables to print
 */
function writeln($text /**, $more_text **/)
{
	$args = func_get_args();
	call_user_func_array('write', $args);

	if(!isCLI())
	{
		write("<br/>");
	}
	else
	{
		write("\n");
	}
}



/**
 * Set exception AND error AND assertion handler.
 * @param callable|null $handler that takes ($message, $script, $line, $trace, $type, $other) and returns bool accordingly if it handled or not
 */
function debug_handler($handler = null)
{
	/* forward  */
	$exception_to_common = null;
	$error_to_common = null;
	$assertion_to_common = null;
	if( null !== $handler )
	{
		$exception_to_common = function($e) use($handler) {
			/** @var Exception $e */
			return $handler($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace(), 'exception', array( 'exception' => $e ));
		};
		$error_to_common = function($number, $message, $script, $line) use($handler) {
			return $handler( $message, $script, $line, array(), 'php_error', array( 'php_error' => $number ) );
		};
		$assertion_to_common = function($script, $line, $message) use ($handler) {
			return $handler( $message, $script, $line, array(), 'assertion', array() );
		};
	}
	debug_handler_exception($exception_to_common);
	debug_handler_error($error_to_common);
	debug_handler_assertion($assertion_to_common);
}

/**
 * @param callable|null $handler that takes ($exception) and returns bool true if handled, false otherwise
 * @return bool|null
 */
function debug_handler_exception($handler = null)
{
	$default_handler = function ($e) {
		/** @var Exception $e */
		$class = get_class($e);
		writeln("Unhandled `{$class}`({$e->getCode()}):");
		writeln("{$e->getFile()}:{$e->getLine()} :: {$e->getMessage()}\n");
		writeln("Backtrace:");
		backtrace_print(0,$e->getTrace());
	};

	$default_handlers = array(
		'development' => $default_handler,
		'testing'     => $default_handler,
		'production'  => function() { return false; }
	);

	if( null == $handler )
	{
		$handler = $default_handlers[ getCurrentEnv() ];
	}
	return set_exception_handler($handler);
}

/**
 * @param callable|null $handler that takes ($errno , $errstr , $errfile , $errline , $errcontext) and returns true if handled, false otherwise
 * @return callable|null
 */
function debug_handler_error($handler = null)
{
	$default_handler = function ($errno , $errstr , $errfile , $errline , $errcontext) {
		throw new ErrorException($errstr.PHP_EOL, $errno, 0, $errfile, $errline);
	};

	$default_handlers = array(
		'development' => $default_handler,
		'testing'     => $default_handler,
		'production'  => function() { return true; }
	);

	if( null == $handler )
	{
		$handler = $default_handlers[ getCurrentEnv() ];
	}
	return set_error_handler($handler);
}

/**
 * @param callable|null $handler that takes ($script, $line, $message) and returns bool accordingly if it handled or not
 * @return callable|null
 */
function debug_handler_assertion($handler = null)
{
	$default_handler = function ($script, $line, $message) {
		throw new Exception("{$script}:{$line} Assertion failed. {$message}");
	};

	$default_handlers = array(
		'development' => $default_handler,
		'testing'     => $default_handler,
		'production'  => function() {  }
	);

	$env = getCurrentEnv();
	if( null == $handler )
	{
		$handler = $default_handlers[ $env ];
	}
	assert_options(ASSERT_QUIET_EVAL, $env=='production');
	assert_options(ASSERT_ACTIVE, $env!='production');
	assert_options(ASSERT_WARNING, true);
	return assert_options(ASSERT_CALLBACK, $handler);
}