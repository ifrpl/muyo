<?php

/**
 * @return bool
 */
function ifrShowDebugOutput()
{
	if(!debugHostAllow() || (APPLICATION_ENV === 'production' && (!isset($_COOKIE['ifrShowDebug']) || $_COOKIE['ifrShowDebug'] !== 'iLuv2ki11BugsBunny!'))) return false;
	return true;
}

/**
 * @return bool
 */
function debugHostAllow()
{
	$allowedSubNet = array(
		'10.10.',
		'192.168.'
	);

	$allowedHosts = array(
		'127.0.0.1',
		'127.0.1.1',
		'10.0.2.2',
		'89.191.162.220', //Lukasz home
		'87.206.45.163',
		'84.10.100.73',
		'89.69.131.15' //IFResearch Chello
	);

	if((isCLI() && APPLICATION_ENV !== 'production'))
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

/**
 * @param mixed $tab
 * @param ...
 */
function printr($tab)
{
	if(!ifrShowDebugOutput()) return null;

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

	file_put_contents(ROOT_PATH.'/tmp/log',$msg,FILE_APPEND);
	writeln($tab);
}

/**
 * @param string $str
 * @param int    $count
 * @param bool   $print
 *
 * @return string|null
 */
function printn($str='',$count=1,$print=true)
{
	if(!ifrShowDebugOutput()) return null;

	if(is_bool($count))
	{
		if(!is_bool($print))
		{
			$tmp = $print;
		}

		$print = $count;

		$count = isset($tmp)?$tmp:1;
	}
	$str.=str_pad('', $count, "\n");

	$print and print($str);

	return $str;
}

/**
 * @param $tab
 */
function printfb($tab)
{
	if(!ifrShowDebugOutput()) return;

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
		$ret .= '['.implode(',', array_map('var_dump_human_compact', $var, array_keys($var))).']';
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
	writeln(backtrace_string($ignore_depth+1, $backtrace));
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

	// _($backtrace)->chain()->map(function($entry){ return isset($entry['file']) ? strlen($entry['file']): 3; })->max()->value();
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
	assert(is_string($ret));
	return $ret;
}

/**
 * @param mixed $tab
 * @param mixed ...
 */
function debug($tab)
{
	if(!ifrShowDebugOutput()) return;

	$trace = backtrace(1);
	if( !isCLI() )
	{
		$f = "{$trace[0]['file']}:{$trace[0]['line']}";

		write("<div style='background-color: #efefef; border: 1px solid #aaaaaa; color:#000;'>");
		write("<div style='font-weight: bold; background-color: #FFF15F; border-bottom: 1px solid #aaaaaa;'><a href='http://localhost:8091?message=$f'>$f</a></div>");
		write("<hr>");
		backtrace_print(0, $trace);
		write("<hr>");
		write(call_user_func_array('var_dump_human_full', func_get_args()));
		write("</div>");
	}
	else
	{
		write("\n======= Debug Break =======\n");
		backtrace_print(0, $trace);
		write("\n===== Debug  Variable =====\n");
		write(call_user_func_array('var_dump_human_full', func_get_args()));
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
		if(!ifrShowDebugOutput()) return null;
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

/**
 * @todo APPLICATION_ENV => handler
 * @param callable $handler assertion handler to be set
 */
function debug_assert_handler($handler = null)
{
	$default_handlers = [
		'development' =>
			function( $script, $line, $message )
			{
				writeln("Assertion Failed:");
				writeln("$script:$line $message\n");

				writeln("Backtrace:");
				backtrace_print(1);
			},
		'production' =>
			function( $script, $line, $message )
			{
				//
			}
	];
	if( null == $handler )
	{
		$handler = $default_handlers[APPLICATION_ENV];
	}
	assert_options(ASSERT_CALLBACK, $handler);
	assert_options(ASSERT_BAIL, APPLICATION_ENV!='production');
	assert_options(ASSERT_QUIET_EVAL, APPLICATION_ENV!='production');
	assert_options(ASSERT_ACTIVE, APPLICATION_ENV!='production');
	assert_options(ASSERT_WARNING, APPLICATION_ENV=='production');
}

/**
 * @todo APPLICATION_ENV => handler
 * @param callable|null $handler_map set error handler to callable
 */
function debug_error_handler($handler = null)
{
	$default_handlers = [
		'development' =>
			function( $number, $message, $script, $line )
			{
				writeln("PHP Error {$number}:");
				writeln("$script:$line $message\n");

				writeln("Backtrace:");
				backtrace_print(1);
				return true;
			},
		'production' =>
			function( $number, $message, $script, $line )
			{
				return false;
			}
	];
	if( null == $handler )
	{
		$handler = $default_handlers[APPLICATION_ENV];
	}
	set_error_handler($handler);
}

/**
 * @todo APPLICATION_ENV => handler
 * @param callable|null $handler to be called on unhandled exception
 */
function debug_exception_handler($handler = null)
{
	$default_handlers = array(
		'development' =>
			function( $e )
			{
				/** @var Exception $e */
				$class = get_class($e);
				writeln("Unhandled `{$class}`({$e->getCode()}):");
				writeln("{$e->getFile()}:{$e->getLine()} {$e->getMessage()}\n");

				writeln("Backtrace:");
				backtrace_print(0,$e->getTrace());
				return true;
			},
		'production' =>
			function( $e )
			{
				return false;
			}
	);
	if( null == $handler )
	{
		$handler = $default_handlers[APPLICATION_ENV];
	}
	set_exception_handler($handler);
}

/**
 * @todo support non-null values
 * @todo APPLICATION_ENV => handler
 * @param handler
 */
function debug_handler($handler = null)
{
	debug_assert_handler($handler);
	debug_error_handler($handler);
	debug_exception_handler($handler);
}

if( class_exists('Zend_Log') )
{
	ifr_assert(function() {
		foreach( [
			LOG_EMERG => Zend_Log::EMERG,
			LOG_ALERT => Zend_Log::ALERT,
			LOG_CRIT => Zend_Log::CRIT,
			LOG_ERR => Zend_Log::ERR,
			LOG_WARNING => Zend_Log::WARN,
			LOG_NOTICE => Zend_Log::NOTICE,
			LOG_INFO => Zend_Log::INFO,
			LOG_DEBUG => Zend_Log::DEBUG
		] as $k => $v)
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
 * @param int  $level
 *
 * @see Zend_Log::WARN
 * @see LOG_WARNING
 *
 * @throws Exception
 */
function ifr_assert($assertion, $message = null, $level = LOG_WARNING)
{
	if( is_callable($assertion) )
	{
		$assertion = $assertion();
	}
	if( version_compare(PHP_VERSION, '5.4.8', '>=') )
	{
		assert($assertion, $message);
	}
	else
	{
		if( is_string($assertion) && ifrShowDebugOutput() )
		{
			$assertion = eval($assertion);
		}
		if( !$assertion )
		{
			if(!$message)
			{
				if( $assertion !== false )
				{
					$assertion = var_dump_human_compact($assertion);
					$message = "Assertion of '{$assertion}' is failed";
				}
				else
				{
					$message = "Assertion failed";
				}
			}

			$exc = new Exception($message);
			if(class_exists('Zend_Controller_Action_HelperBroker'))
			{
				Zend_Controller_Action_HelperBroker::getStaticHelper('log')->log($exc, $level);
			}

			throw $exc;
		}
	}
}

/**
 * @param mixed $text variables to print
 * @param mixed ... more variables to print
 */
function write($text /**, $more_text **/)
{
	if(!ifrShowDebugOutput()) return null;

	ob_start();
	echo implode(',',array_map(function($arg)
	{
		if ( is_string($arg) )
		{
			echo($arg);
		}
		else
		{
			echo var_dump_human_compact($arg);
		}
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
	write("\n");
}