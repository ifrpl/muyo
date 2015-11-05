<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)

require_once __DIR__.'/string.php';

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
			'192.168.',
			'172.16.'
		);

		$allowedHosts = array(
			'10.0.2.2',
			'89.191.162.220', //Lukasz home
			'87.206.45.163',
			'84.10.100.73',
			'89.69.131.15' //IFResearch Chello
		);

		if((isCLI() && getCurrentEnv() !== 'production'))
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

if( !function_exists('debug_allow') )
{
	/**
	 * @return bool
	 */
	function debug_allow()
	{
		return !in_array(getCurrentEnv(), [ENV_PRODUCTION, ENV_TESTING]);
	}
}

if( !function_exists('printr') )
{
	/**
	 * @param mixed $tab
	 * @param bool|null $cli
	 */
	function printr( $tab, $cli=null )
	{
		if(!debug_allow()) return;

		if( func_num_args() > 1 )
		{
			$tab = func_get_args();
		}
		$dbg = debug_backtrace();
		if( is_null($cli) )
		{
			$cli = isCLI();
		}
		if( !$cli )
		{
			write( "<pre style='background-color: #efefef; border: 1px solid #aaaaaa; color:#000;'>" );
			$id = defined('__ROOT') ? uniqid() : null;
			if( $id!==null )
			{
				write( "<iframe name='{$id}' style='display: none;'></iframe>" );
			}
			$f = "{$dbg[0]['file']}:{$dbg[0]['line']}";
			write( "<div style='font-weight: bold; background-color: #FFF15F; border-bottom: 1px solid #aaaaaa;'>" );
			$target = $id!==null ? " target='{$id}'" : '';
			write( "<a href='http://localhost:8091?message=$f'{$target}>$f</a></div>" );
		}
		$tmp = print_r($tab,true);

		if($cli === 2)
		{
			$tmp = str_replace("\n",'',$tmp);
		}
		echo $tmp;
		if(!$cli)
		{
			write( "</pre>\n" );
		}
		else
		{
			writeln('');
		}
	}
}

if( !function_exists('printrlog') )
{
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

		$logdir = defined('ROOT_PATH') ? ROOT_PATH : '';
        $logdir .= '/tmp';
		if( !is_dir($logdir) )
		{
			mkdir($logdir, 0777, true);
		}
		file_put_contents($logdir . DIRECTORY_SEPARATOR . 'log', $msg, FILE_APPEND);
		writeln($tab);
	}
}

if( !function_exists('printn') )
{
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
}

if( !function_exists('printfb') )
{
	/**
	 * @param mixed $tab
	 */
	function printfb($tab)
	{
		if( debug_allow() )
		{
			header( 'debug: '.var_dump_human_full($tab) );
		}
	}
}

if( !function_exists('backtrace') )
{
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
}

if( !function_exists('var_dump_human_full') )
{
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
}

if( !function_exists('var_dump_html_full') )
{
	/**
	 * @param mixed $var
	 * @param int $max_size
	 *
	 * @return string
	 */
	function var_dump_html_full($var,$max_size=100000)
	{
		return '<pre>'.var_dump_human_full($var).'</pre>';
	}
}

if( !function_exists('var_dump_human_compact') )
{
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
				$type = is_object($var) ? get_class($var) : gettype($var);
				$ret .= '&'.$type;
			}
			else
			{
				$tmp = array();
				foreach( $var as $k=>$v )
				{

					$tmp []= var_dump_human_compact($v, $k);
				}
				$ret .= '['.str_truncate(implode(',',$tmp)).']';
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
			if ( in_array($class,['Object','stdClass']) )
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
		return str_truncate($ret);
	}
}

if( !function_exists('backtrace_print') )
{
	/**
	 * @param int $ignore_depth stack frames to ignore
	 * @param null|mixed $backtrace backtrace array to print
	 */
	function backtrace_print($ignore_depth = 0, $backtrace = null)
	{
		if( debug_allow() )
		{
			$text = backtrace_string($ignore_depth+1, $backtrace);
			writeln($text);
		}
	}
}

if( !function_exists('backtrace_string') )
{

    /**
     * @param int $ignore_depth stack frames to ignore
     * @param null|mixed $backtrace backtrace array to print
     * @return string
     */
    function backtrace_string($ignore_depth = 0, $backtrace = null)
    {
        if( is_null($backtrace) )
        {
            $backtrace = backtrace($ignore_depth+1);
        }

	    $clbs = [];

	    if(isDev())
	    {
		    \IFR\Cli\Git::blameStacktrace($backtrace);

		    $clbs += [
			    'time' => function($val)
			    {
				    return isset($val['git']['author-time']) ? $val['git']['author-time'] : '';
			    },
			    'author' => function($val)
			    {
				    static $MAX_AUTHOR_LENGTH = 15;

				    $ret = isset($val['git']['author']) ? $val['git']['author'] : '';

				    if(strlen($ret) > $MAX_AUTHOR_LENGTH)
				    {
					    $ret = substr($ret, 0, $MAX_AUTHOR_LENGTH - 3) . '...';
				    }

				    return $ret;
			    }
		    ];
	    }

		$clbs += [
            'file' => function($val)
            {
                return isset($val['file']) ? trim_application_path($val['file']) : '';
            },
            'line' => function($val)
            {
                return isset($val['line']) ? (string)$val['line'] : '';
            },
            'function' => function($val)
            {
                return isset($val['function']) ? $val['function'] : '';
            },
            'args' => function($val)
            {
                return isset($val['args']) && is_array($val['args']) ? implode(',',array_map_val($val['args'],function($v){ return var_dump_human_compact($v); })) : '';
            }
        ];

        $keys = array_keys($clbs);
        unset($keys[array_search('args', $keys)]);

        $max_len = array_combine(
            $keys,
            array_pad([], count($keys), 0)
        );

        foreach( $backtrace as & $val0 )
        {
            foreach($max_len as $key => $max)
            {
                $val0[$key] = $clbs[$key]($val0);
                $max_len[$key] = max($max, strlen($val0[$key]));
            }
        }

        $ret = '';
        foreach( $backtrace as $val1 )
        {
            foreach($max_len as $key => $max)
            {
                $ret .= str_pad($val1[$key], $max_len[$key] + 1);
            }

            $ret .= sprintf("(%s)\n", $clbs['args']($val1));
        }
        return $ret;
    }
}

if( !function_exists('backtrace_html') )
{
	/**
	 * @param int  $ignore_depth
	 * @param null $backtrace
	 *
	 * @return string
	 */
	function backtrace_html($ignore_depth = 0, $backtrace = null)
	{
		return "<pre>\n".backtrace_string($ignore_depth,$backtrace)."\n</pre>";
	}
}

if( !function_exists('debug') )
{
	/**
	 * @param mixed $tab
	 * @param mixed ...
	 */
	function debug($tab)
	{
		call_user_func_array( 'debug_full', func_get_args() );
	}
}

if( !function_exists('debug_full') )
{
	/**
	 * @param mixed $tab
	 * @param mixed ...
	 */
	function debug_full($tab)
	{
		if(!debug_allow()) return;

		$trace = backtrace(1);
		if( !isCLI() )
		{
			write("<pre style='background-color: #efefef; border: 1px solid #aaaaaa; color:#000;'>");

			$traceFile = backtrace();
			$fFile = array_key_exists( 'file', $traceFile[0] ) ? $traceFile[0]['file'] : '???';
			$fLine = array_key_exists( 'line', $traceFile[0] ) ? $traceFile[0]['line'] : '???';
			$f = "{$fFile}:{$fLine}";
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
}

if( !function_exists('debug_compact') )
{
	/**
	 * @param ... $args
	 */
	function debug_compact()
	{
		debug(var_dump_human_compact(func_get_args()));
	}
}

if( !function_exists('get_call_stack') )
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

if( !function_exists('debug_assert_native') )
{
	/**
	 * @param bool|null $value
	 * @return mixed
	 */
	function debug_assert_native( $value=null )
	{
		static $debug_assert_native = false;
		$ret = $debug_assert_native;
		if( null !== $value )
		{
			$debug_assert_native = $value;
		}
		return $ret;
	}
}

if( !function_exists('debug_assert') )
{
	if( debug_assert_native() && version_compare(PHP_VERSION, '5.4.8', '>=') )
	{
		/**
		 * @param mixed $assertion
		 * @param string|null $message
		 *
		 * @return mixed $assertion
		 */
		function debug_assert( $assertion, $message = null )
		{
			if( is_callable($assertion) )
			{
				$validAssertion = $assertion();
			}
			else
			{
				$validAssertion = $assertion;
			}
			assert( $validAssertion, is_string($message) ? $message : var_dump_human_compact($message) );
			return $assertion;
		}
	}
	else
	{
		/**
		 * @param mixed $assertion
		 * @param string|null $message
		 *
		 * @return mixed $assertion
		 */
		function debug_assert($assertion, $message = null)
		{
			if( is_callable($assertion) )
			{
				$assertion = $assertion();
			}
			if( is_string($assertion) )
			{
				$assertion = eval($assertion);
			}

			if( !$assertion )
			{
				$handler = assert_options(ASSERT_CALLBACK);
				$message = var_dump_human_compact($message);

				if( null === $handler )
				{
					$handler = debug_handler_assertion_default_dg();
				}
				/** @var Callable $handler */

				$trace = backtrace(1);
				$file = isset($trace['file']) ? $trace['file'] : '';
				$line = isset($trace['line']) ? $trace['line'] : '';
				$handler($file, $line, $message);
			}
			return $assertion;
		}
	}
}

if( !function_exists('debug_enforce') )
{
	/**
	 * @param mixed       $enforcement
	 * @param string|null $message
	 *
	 * @throws Exception
	 * @return mixed
	 */
	function debug_enforce($enforcement, $message = null)
	{
		if( $message === null )
		{
			$message = 'Enforcement failed';
		}
		if( is_callable($enforcement) )
		{
			$enforcement = $enforcement();
		}
		if( !$enforcement )
		{
			throw new Exception(var_dump_human_compact($message));
		}
		else
		{
			return $enforcement;
		}
	}
}

if( !function_exists('debug_trace_func_call') )
{
	/**
	 * @param int $stack_index
	 * @param int $options debug_backtrace options
	 * @return array compatible with debug_backtrace
	 */
	function debug_trace_func_call($stack_index = 0, $options = 0)
	{
		$stack_index+=2; //ignore myself
		$backtrace_args = array($options);
		if( version_compare(PHP_VERSION, '5.4.0', '>=') )
		{
			$backtrace_args []= $stack_index+1;
		}
		$tmp = call_user_func_array('debug_backtrace', $backtrace_args);
		return array( $tmp[$stack_index] );
	}
}

if( !function_exists('write') )
{
	/**
	 * @param mixed $text variables to print
	 * @param mixed ... more variables to print
	 */
	function write($text /**, $more_text **/)
	{
		$output = implode(
			',',
			array_map(
				function($arg)
				{
					if ( !is_string($arg) )
					{
						$arg = var_dump_human_compact($arg);
					}

					if( !isCLI() )
					{
						$arg = str_replace("\n", "<br/>", $arg);
					}

					return $arg;
				},
				func_get_args()
			)
		);

		if( !isCLI() )
		{
			ob_start();
			echo $output;
			ob_end_flush();

		}
		else
		{
			fwrite(STDOUT, $output);
		}
	}
}

if( !function_exists('writeln') )
{
	/**
	 * @param mixed $text variables to print
	 * @param mixed ... more variables to print
	 */
	function writeln($text /**, $more_text **/)
	{
		$args = func_get_args();
		call_user_func_array('write', $args);

		if( !isCLI() )
		{
			write("<br/>");
		}
		else
		{
			write("\n");
		}
	}
}

if( !function_exists('debug_handler') )
{
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
			$exception_to_common = function($e) use($handler)
			{
				/** @var Exception $e */
				return $handler($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTrace(), 'exception', array( 'exception' => $e ));
			};
			$error_to_common = function($number, $message, $script, $line) use($handler)
			{
				return $handler( $message, $script, $line, array(), 'php_error', array( 'php_error' => $number ) );
			};
			$assertion_to_common = function($script, $line, $message) use ($handler)
			{
				return $handler( $message, $script, $line, array(), 'assertion', array() );
			};
		}
		debug_handler_exception($exception_to_common);
		debug_handler_error($error_to_common);
		debug_handler_assertion($assertion_to_common);
	}
}

if( !function_exists('debug_handler_exception_default_dg') )
{
	/**
	 * @return callable
	 */
	function debug_handler_exception_default_dg()
	{
		return function( $e )
		{
			Logger::error( $e );
			exit(1);
		};
	}
}

if( !function_exists('debug_handler_exception') )
{
	/**
	 * @param callable|null $handler that takes ($exception) and returns bool true if handled, false otherwise
	 * @return bool|null
	 */
	function debug_handler_exception($handler = null)
	{
		if( null == $handler )
		{
			$handler = debug_handler_exception_default_dg();
		}
		return set_exception_handler($handler);
	}
}

if( !function_exists('debug_handler_error_default_dg') )
{
	/**
	 * @return callable
	 */
	function debug_handler_error_default_dg()
	{
		return function ($errno, $errstr, $errfile, $errline, $errcontext)
		{
			$e = new ErrorException($errstr . PHP_EOL, $errno, 0, $errfile, $errline);
			switch( $errno )
			{
				case E_ERROR:
				case E_PARSE:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
				case E_RECOVERABLE_ERROR:
					throw $e;
					break;
				case E_WARNING:
				case E_CORE_WARNING:
				case E_COMPILE_WARNING:
				case E_USER_WARNING:
					logger_log( $e, LOG_WARNING );
					break;
				case E_NOTICE:
				case E_USER_NOTICE:
				case E_DEPRECATED:
				case E_USER_DEPRECATED:
					logger_log( $e, LOG_NOTICE );
					break;
				default:
					logger_log( new ErrorException("Unknown error value ($errno)", 0, 0, __FILE__, __LINE__, $e), LOG_NOTICE );
					break;
			}
		};
	}
}

if( !function_exists('debug_handler_error') )
{
	/**
	 * @param callable|null $handler that takes ($errno , $errstr , $errfile , $errline , $errcontext) and returns true if handled, false otherwise
	 * @return callable|null
	 */
	function debug_handler_error($handler = null)
	{
		if( null == $handler )
		{
			$handler = debug_handler_error_default_dg();
		}
		$ret = set_error_handler( $handler, -1 );
		return $ret;
	}
}

if( !function_exists('debug_handler_assertion_default_dg') )
{
	/**
	 * @return callable
	 */
	function debug_handler_assertion_default_dg()
	{
		return function ($script, $line, $message)
		{
			$e = new Exception("{$script}:{$line} Assertion failed. {$message}");
			if( ENV_DEVELOPMENT == getCurrentEnv() )
			{
				throw $e;
			}
			else
			{
				Logger::error( $e );
			}
		};
	}
}

if( !function_exists('debug_handler_assertion') )
{
	/**
	 * @param callable|null $handler that takes ($script, $line, $message) and returns bool accordingly if it handled or not
	 * @return callable|null
	 */
	function debug_handler_assertion($handler = null)
	{
		$env = getCurrentEnv();
		if( null == $handler )
		{
			$handler = debug_handler_assertion_default_dg();
		}
		assert_options(ASSERT_ACTIVE, true);
		assert_options(ASSERT_WARNING, false);
		assert_options(ASSERT_BAIL, false);
		assert_options(ASSERT_QUIET_EVAL, $env==='production');
		return assert_options(ASSERT_CALLBACK, $handler);
	}
}

if( !function_exists('debug_enforce_type') )
{
	/**
	 * Enforces that gettype($var) === $type
	 *
	 * @param mixed $var
	 * @param string $type
	 * @return mixed
	 */
	function debug_enforce_type( $var, $type )
	{
		debug_enforce( is_type( $var, $type ), "Expected parameter of type ".var_dump_human_compact($type) );
		return $var;
	}
}

if( !function_exists('debug_enforce_type_dg') )
{
	function debug_enforce_type_dg( $type, $var=null )
	{
		if( is_string($type) )
		{
			$type = return_dg($type);
		}
		else
		{
			debug_enforce_type( $type, 'callable' );
		}
		if( null===$var )
		{
			$var = tuple_get();
		}
		else
		{
			debug_enforce_type( $var, 'callable' );
		}
		return function()use($type,$var)
		{
			$args = func_get_args();
			return debug_enforce_type(
				call_user_func_array( $var, $args ),
				call_user_func_array( $type, $args )
			);
		};
	}
}

if( !function_exists('debug_enforce_string') )
{
	/**
	 * @param mixed $var
	 * @return string
	 */
	function debug_enforce_string($var)
	{
		debug_enforce_type( $var, 'string' );
		return $var;
	}
}

if( !function_exists('debug_enforce_gte') )
{
	/**
	 * Enforces that $var >= $count
	 * @param number $var
	 * @param number $count
	 * @return number
	 */
	function debug_enforce_gte($var,$count)
	{
		debug_enforce( $var >= $count );
		return $var;
	}
}

if( !function_exists('debug_enforce_count_gte') )
{
	/**
	 * Enforces that count($var) >= $count
	 *
	 * @param array $var
	 * @param int $count
	 */
	function debug_enforce_count_gte($var,$count)
	{
		$c = count($var);
		debug_enforce( $c >= $count, "Expected array count >= $c, but $count given" );
	}
}

if( !function_exists('debug_enforce_key_exists') )
{
	/**
	 * Enforces that true === array_key_exists($key,$arr)
	 *
	 * @param string $key
	 * @param array $arr
	 */
	function debug_enforce_key_exists($key,$arr)
	{
		arrayize($key);
		debug_enforce(
			array_all( $key, function( $key )use( $arr )
			{
				return array_key_exists( $key, $arr );
			}),
			"Expected key(s) '".implode(',',$key)."' does not exists."
		);
	}
}

if( !function_exists('exception_str') )
{
	/**
	 * @param \Exception $exception
	 * @param string $eol
	 * @return string
	 */
	function exception_str( $exception, $eol=null )
	{
		if( $eol===null )
		{
			$eol="\n";
		}
		$ret = '';
		$prefix="Uncaught";
		if( debug_assert( $exception instanceof \Exception ) )
		{
			do
			{
				$class = get_class( $exception );
				$msg   = $exception->getMessage();
				$file  = $exception->getFile();
				$line  = $exception->getLine();
				$trace = backtrace_string( 0, $exception->getTrace() );
				$ret  .= "$prefix $class in $file:$line".$eol.
                    str_indent( 'Code: ' . $exception->getCode()) . $eol .
					str_indent( 'Message:' . $eol.
						implode(
							$eol,
							array_map_val(
								explode( $eol, $msg ),
								function($val,$key){ return str_indent( $val, 1 ); }
							)
						)
					, 1 ).$eol
				;
				if( !empty($trace) )
				{
					$ret  .=
						str_indent( "Backtrace:".$eol.
							str_indent( $trace, 1 )
						, 1 ).$eol
					;
				}

				$prefix="Caused by";
				$exception = $exception->getPrevious();
			}
			while( $exception !== null );
		}
		return $ret;
	}
}

if( !function_exists('debug_assert_count_eq') )
{
	/**
	 * @param array $countable
	 * @param int $integer
	 * @return array
	 */
	function debug_assert_count_eq( $countable, $integer )
	{
		$count = count( $countable );
		return debug_assert( $count === $integer, "Expected count=='$integer', got '$count'" );
	}
}

if( !function_exists('debug_assert_type') )
{
	/**
	 * Asserts that gettype($var) === $type
	 *
	 * @param mixed $var
	 * @param string $type
	 * @return mixed
	 */
	function debug_assert_type($var,$type)
	{
		debug_assert( is_type( $var, $type ), "Expected parameter of type ".var_dump_human_compact($type) );
		return $var;
	}
}

if( !function_exists('debug_assert_array_contains') )
{
	/**
	 * @param array $array
	 * @param mixed $value
	 * @return bool
	 */
	function debug_assert_array_contains( $array, $value )
	{
		return debug_assert( array_contains( $array, $value ), "Parameter {$value} is not one of ".implode( ',', $array) );
	}
}

if( !function_exists('debug_enforce_eq') )
{
	/**
	 * @param mixed $a
	 * @param mixed $b
	 * @param bool $strong typing
	 * @return mixed
	 */
	function debug_enforce_eq( $a, $b, $strong=false )
	{
		return debug_enforce( $strong ? ($a===$b) : ($a==$b),
			var_dump_human_compact( $a )." doesn't equals to ".var_dump_human_compact( $b )
		);
	}
}

if( !function_exists('debug_print_backtrace_alt') )
{
	function debug_print_backtrace_alt($callstackDepth = 0)
	{
		Logger::dump(debug_backtrace (DEBUG_BACKTRACE_IGNORE_ARGS, $callstackDepth));
	}
}

if( !function_exists('debug_assert_empty') )
{
	/**
	 * @param mixed $variable
	 * @return mixed
	 */
	function debug_assert_empty($variable)
	{
		if( !empty($variable) )
		{
			debug_assert( false, "Assertion of empty `".var_dump_human_compact($variable)."` failed" );
		}
		return $variable;
	}
}

if( !function_exists('ensure') )
{
	/**
	 * @param mixed $subject
	 * @param callable $predicate
	 * @param callable|null $on_fail
	 * @return mixed
	 */
	function ensure( $subject, $predicate, $on_fail=null )
	{
		if( null===$on_fail )
		{
			$on_fail = function($subject)
			{
				debug_assert( false, "Could not assure about variable: ".var_dump_human_compact($subject) );
				return $subject;
			};
		}

		if( call_user_func( $predicate, $subject ) )
		{
			$ret = $subject;
		}
		else
		{
			$ret = call_user_func( $on_fail, $subject );
		}
		return $ret;
	}
}

if( !function_exists('paranoid') )
{
	function paranoid()
	{
		set_error_handler(
			function ($errno, $errstr, $errfile, $errline)
			{
				$exception = new Exception($errstr . '; File: '.$errfile.':'.$errline, $errno);
				throw $exception;
			}
		);
	}
}