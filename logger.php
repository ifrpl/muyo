<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)

if( !class_exists('Logger') )
{
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

            $outputDirPath = defined('ROOT_PATH') ? ROOT_PATH : '';
			$outputDirPath .= DIRECTORY_SEPARATOR . 'data/tmp/dump/' . $id;

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
}

const LOG_ATTRIBUTE_TIME = 'time';
const LOG_ATTRIBUTE_SEVERITY = 'severity';
const LOG_ATTRIBUTE_MESSAGE = 'message';
const LOG_ATTRIBUTE_TRACE = 'trace';
const LOG_ATTRIBUTE_VERSION = 'version';
const LOG_ATTRIBUTE_HTTP = 'http';

if( !function_exists('log_event_factory_default_dg') )
{
	function log_event_factory_default_dg()
	{
		return array_chain_dg(
			tuple_get(0),
			if_dg(
				array_some_dg(log_event_attributes_dg(),eq_dg(log_attribute_key_dg(),return_dg(LOG_ATTRIBUTE_TIME))),
				tuple_get(0),
				list_append_dg( return_dg(log_attribute(LOG_ATTRIBUTE_TIME,date_create())) )
			)
		);
	}
}

if( !function_exists('log_event_factory_dg') )
{
	/**
	 * @param null $value
	 * @return callable
	 */
	function log_event_factory_dg($value=null)
	{
		static $factory=null;
		$ret = $factory;
		if( $value!==null )
		{
			$factory = $value;
		}
		return $ret===null ? log_event_factory_default_dg() : $ret;
	}
}

if( !function_exists('log_event') )
{
	/**
	 * @param array $attributes
	 * @param callable|null  $factory
	 * @return array
	 */
	function log_event( $attributes=[], $factory=null )
	{
		if( $factory===null )
		{
			$factory = log_event_factory_dg();
		}
		return $factory( $attributes );
	}
}

if( !function_exists('log_event_attributes_default_dg') )
{
	function log_event_attributes_default_dg()
	{
		return tuple_get(0);
	}
}

if( !function_exists('log_event_attributes_dg') )
{
	/**
	 * @param null|callable $getter
	 * @return callable
	 */
	function log_event_attributes_dg( $getter=null )
	{
		static $factory=null;
		$ret = $factory;
		if( $getter!==null )
		{
			$factory = $getter;
		}
		return $ret===null ? log_event_attributes_default_dg() : $ret;
	}
}

if( !function_exists('log_attribute_factory_default_dg') )
{
	/**
	 * @return callable
	 */
	function log_attribute_factory_default_dg()
	{
		return function($key,$value)
		{
			return [$key,$value];
		};
	}
}

if( !function_exists('log_attribute_key_default_dg') )
{
	/**
	 * @return callable
	 */
	function log_attribute_key_default_dg()
	{
		return array_get_dg( 0, tuple_get(0) );
	}
}

if( !function_exists('log_attribute_val_default_dg') )
{
	/**
	 * @return callable
	 */
	function log_attribute_val_default_dg()
	{
		return array_get_dg( 1, tuple_get(0) );
	}
}

if( !function_exists('log_attribute_factory_dg') )
{
	/**
	 * @param null|callable $value ($key,$value)=>$attribute
	 * @return callable
	 */
	function log_attribute_factory_dg($value=null)
	{
		static $factory = null;
		$ret = $factory;
		if( $value!==null )
		{
			$factory = $value;
		}
		if( $ret===null )
		{
			$ret = log_attribute_factory_default_dg();
		}
		return $ret;
	}
}

if( !function_exists('log_attribute') )
{
	/**
	 * @param string $key
	 * @param mixed $value
	 * @param callable|null $factory
	 * @return array
	 */
	function log_attribute( $key, $value, $factory=null )
	{
		if( $factory===null )
		{
			$factory = log_attribute_factory_dg();
		}
		return $factory($key,$value);
	}
}

if( !function_exists('log_attribute_key_dg') )
{
	/**
	 * @param callable|null $getter
	 * @return callable
	 */
	function log_attribute_key_dg($getter=null)
	{
		static $factory=null;
		$ret = $factory;
		if( $getter!==null )
		{
			debug($getter);
			$factory = $getter;
		}
		return $ret===null ? log_attribute_key_default_dg() : $ret;
	}
}

if( !function_exists('log_attribute_val_dg') )
{
	/**
	 * @param callable|null $getter
	 * @return callable
	 */
	function log_attribute_val_dg($getter=null)
	{
		static $factory=null;
		$ret = $factory;
		if( $getter!==null )
		{
			$factory = $getter;
		}
		return $ret===null ? log_attribute_val_default_dg() : $ret;
	}
}

if( !function_exists('log_target') )
{
	/**
	 * @param callable $executor
	 * @param callable|null $filter
	 * @param callable|null $formatter
	 * @return array
	 */
	function log_target( $executor, $filter=null, $formatter=null )
	{
		if( $filter===null )
		{
			$filter = return_dg(true);
		}
		if( $formatter===null )
		{
			$formatter = tuple_get(0);
		}
		return [$executor,$filter,$formatter];
	}
}

if( !function_exists('log_target_executor') )
{
	/**
	 * @param array $target
	 * @return callable
	 */
	function log_target_executor( $target )
	{
		return $target[0];
	}
}

if( !function_exists('log_target_executor_dg') )
{
	/**
	 * @param array|null $target
	 * @return callable
	 */
	function log_target_executor_dg( $target=null )
	{
		if( $target===null )
		{
			$target = tuple_get(0);
		}
		else
		{
			$target = callablize($target);
		}

		return function () use ( $target )
		{
			$args = func_get_args();
			return log_target_executor(
				call_user_func_array( $target, $args )
			);
		};
	}
}

if( !function_exists('log_target_filter') )
{
	/**
	 * @param array $target
	 * @return callable
	 */
	function log_target_filter( $target )
	{
		return $target[1];
	}
}

if( !function_exists('log_target_filter_dg') )
{
	/**
	 * @param array|null $target
	 * @return callable
	 */
	function log_target_filter_dg( $target=null )
	{
		if( $target===null )
		{
			$target = tuple_get(0);
		}
		else
		{
			$target = callablize($target);
		}

		return function () use ( $target )
		{
			$args = func_get_args();
			return log_target_filter(
				call_user_func_array( $target, $args )
			);
		};
	}
}

if( !function_exists('log_target_formatter') )
{
	/**
	 * @param array $target
	 * @return callable
	 */
	function log_target_formatter( $target )
	{
		return $target[2];
	}
}

if( !function_exists('log_target_filter_dg') )
{
	/**
	 * @param array|null $target
	 * @return callable
	 */
	function log_target_filter_dg( $target=null )
	{
		if( $target===null )
		{
			$target = tuple_get(0);
		}
		else
		{
			$target = callablize($target);
		}

		return function () use ( $target )
		{
			$args = func_get_args();
			return log_target_filter(
				call_user_func_array( $target, $args )
			);
		};
	}
}

if( !function_exists('log_target_formatter_dg') )
{
	/**
	 * @param array|null $target
	 * @return callable
	 */
	function log_target_formatter_dg( $target=null )
	{
		if( $target===null )
		{
			$target = tuple_get(0);
		}
		else
		{
			$target = callablize($target);
		}

		return function () use ( $target )
		{
			$args = func_get_args();
			return log_target_formatter(
				call_user_func_array( $target, $args )
			);
		};
	}
}

if( !function_exists('log_target_default_stdout') )
{
	function log_target_default_stdout()
	{
		return log_target(
			writeln_dg(),
			if_dg(
				isCli(),
				switch_dg(
					getCurrentEnv(),
					[
						ENV_DEVELOPMENT,
						array_some_dg(
							log_event_attributes_dg(),
							and_dg(
								eq_dg(log_attribute_key_dg(),return_dg(LOG_ATTRIBUTE_SEVERITY)),
								array_contains_dg(log_attribute_val_dg(),[LOG_EMERG,LOG_ALERT,LOG_CRIT,LOG_ERR,LOG_WARNING,LOG_NOTICE,LOG_INFO])
							)
						)
					],
					array_some_dg(
						log_event_attributes_dg(),
						and_dg(
							eq_dg(log_attribute_key_dg(),return_dg(LOG_ATTRIBUTE_SEVERITY)),
							array_contains_dg(log_attribute_val_dg(),[LOG_EMERG,LOG_ALERT,LOG_CRIT,LOG_ERR,LOG_INFO])
						)
					)
				),
				return_dg(false)
			),
			function( $event )
			{
				$attributes = call_user_func( log_event_attributes_dg(), $event );

				$timeIdx = array_find_key( $attributes, eq_dg( log_attribute_key_dg(), return_dg(LOG_ATTRIBUTE_TIME) ) );
				if( $timeIdx===null )
				{
					$time = '??-??-?? ??:??:??';
				}
				else
				{
					$time = date_format( call_user_func( log_attribute_val_dg(), $attributes[$timeIdx] ), 'y-m-d H:i:s' );
					unset($attributes[$timeIdx]);
				}

				$severityIdx = array_find_key( $attributes, eq_dg( log_attribute_key_dg(), return_dg(LOG_ATTRIBUTE_SEVERITY) ) );
				if( $severityIdx===null )
				{
					$severity = "???????";
				}
				else
				{
					$severity = log_level_str( call_user_func( log_attribute_val_dg(), $attributes[$severityIdx] ) );
					unset($attributes[$severityIdx]);
				}

				return array_chain(
					$attributes,
					array_map_val_dg(
						function($attribute)use( $time, $severity )
						{
							$key = call_user_func( log_attribute_key_dg(), $attribute );
							$val = call_user_func( log_attribute_val_dg(), $attribute );
							$lines = array_map_val( explode( PHP_EOL, $val ), str_indent_dg() );
							array_unshift( $lines, ucwords($key).":" );
							return array_map_val( $lines, str_prepend_dg("$time $severity ") );
						}
					),
					array_flatten_dg(),
					array_implode_dg( PHP_EOL )
				);
			}
		);
	}
}

if( !function_exists('log_target_file_path_default') )
{
	/**
	 * @return string
	 * @fixme reuse of printrlog logic
	 */
	function log_target_file_path_default()
	{
		$parent = defined('ROOT_DIR') ? ROOT_DIR : '/';
		$dir = ensure($parent,str_endswith_dg(DIRECTORY_SEPARATOR),str_append_dg(DIRECTORY_SEPARATOR)).'tmp';
		return $dir.DIRECTORY_SEPARATOR.'log';
	}
}

if( !function_exists('log_target_file_path') )
{
	function log_target_file_path($value=null)
	{
		static $factory=null;
		$ret = $factory;
		if( $value!==null )
		{
			$factory = $value;
		}
		return $ret===null ? log_target_file_path_default() : $ret;
	}
}

if( !function_exists('log_targets_default') )
{
	function log_targets_default()
	{
		return [
			log_target_default_stdout(),
//			log_target_default_file()
		];
	}
}

if( !function_exists('log_targets') )
{
	/**
	 * @param array|null $value
	 * @return array
	 */
	function log_targets($value=null)
	{
		static $targets = null;
		$ret = $targets;
		if( $value!==null )
		{
			$targets = $value;
		}
		return $ret===null ? log_targets_default() : $ret;
	}
}

if( !function_exists('log_filter_event') )
{
	function log_filter_event( $filter, $event )
	{
		return $filter( $event );
	}
}

if( !function_exists('log_filter_event_dg') )
{
	function log_filter_event_dg( $event, $filter=null )
	{
		$event = callablize($event);
		if( $filter===null )
		{
			$filter = tuple_get(0);
		}

		return function () use ( $event, $filter )
		{
			$args = func_get_args();
			return log_filter_event(
				call_user_func_array( $filter, $args ),
				call_user_func_array( $event, $args )
			);
		};
	}
}

if( !function_exists('log_formatter_event') )
{
	/**
	 * @param callable $formatter
	 * @param array $event
	 * @return mixed
	 */
	function log_formatter_event( $formatter, $event )
	{
		return call_user_func( $formatter, $event );
	}
}

if( !function_exists('log_formatter_event_dg') )
{
	function log_formatter_event_dg( $event, $formatter=null )
	{
		$event = callablize($event);
		if( $formatter===null )
		{
			$formatter = tuple_get(0);
		}
		return function() use( $event, $formatter )
		{
			$args = func_get_args();
			return log_formatter_event(
				call_user_func_array( $formatter, $args ),
				call_user_func_array( $event, $args )
			);
		};
	}
}

if( !function_exists('logger_log_tmp') )
{
	function logger_log_tmp($message,$level=LOG_INFO)
	{
		$attributes = [log_attribute(LOG_ATTRIBUTE_SEVERITY,$level)];
		if( $message instanceof Exception )
		{
			$attributes []= log_attribute(LOG_ATTRIBUTE_MESSAGE,$message->getMessage());
			$attributes []= log_attribute(LOG_ATTRIBUTE_TRACE,$message->getTrace());
			$attributes []= log_attribute(LOG_ATTRIBUTE_TRACE,$message->getCode());
			$attributes []= log_attribute(LOG_ATTRIBUTE_TRACE,$message->getFile());
			$attributes []= log_attribute(LOG_ATTRIBUTE_TRACE,$message->getLine());
			$attributes []= log_attribute(LOG_ATTRIBUTE_TRACE,$message->getPrevious());
		}
		elseif( !is_string($val) )
		{
			$val = var_dump_human_compact($val);
		}
//		$attributes []= log_attribute(LOG_ATTRIBUTE_HTTP,);
		$event = log_event( $attributes );
		array_chain(
			log_targets(),
			array_filter_key_dg( log_filter_event_dg( $event, log_target_filter_dg() ) ),
			array_map_val_dg( array_dg( log_target_executor_dg(), log_formatter_event_dg( $event, log_target_formatter_dg() ) ) ),
			array_each_dg( function($tuple){ return $tuple[0]($tuple[1]); } )
		);
	}
}

if( !function_exists('logger_log') )
{
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
		$logger = logger_get();
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
}

if( !function_exists('logger_get') )
{
	/**
	 * @return callable
	 */
	function logger_get()
	{
		global $logger;
		return $logger===null ? logger_default() : $logger;
	}
}

if( !function_exists('logger_set') )
{
	/**
	 * @param callable|null $val that takes ($message, $level)
	 */
	function logger_set( $val=null )
	{
		global $logger;

		if( $val!==null && !debug_assert_type( $val, 'callable' ) )
		{
			$val = null;
		}

		$logger = $val;
	}
}

if( !function_exists('logger_default') )
{
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
}

if( !function_exists('logger_rotate') )
{
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
}

if( !function_exists('log_level_str') )
{
	/**
	 * @param int $level
	 * @return string
	 */
	function log_level_str($level)
	{
		$map = [
			LOG_EMERG => 'EMERG',
			LOG_ALERT => 'ALERT',
			LOG_CRIT => 'CRIT',
			LOG_ERR => 'ERR',
			LOG_WARNING => 'WARNING',
			LOG_NOTICE => 'NOTICE',
			LOG_INFO => 'INFO',
			LOG_DEBUG => 'DEBUG',
		];
		if( debug_assert( array_key_exists( $level, $map ), 'Unknown log level' ) )
		{
			$level = $map[ $level ];
		}
		return $level;
	}
}

if( !function_exists('logger_log_compact') )
{
	/**
	 * @param mixed $var
	 * @param int $level
	 * @return null
	 */
	function logger_log_compact($var,$level=LOG_DEBUG)
	{
		return logger_log( var_dump_human_compact($var), $level );
	}
}

if( !function_exists('logger_log_full') )
{
	/**
	 * @param mixed $var
	 * @param int $level
	 * @return null
	 */
	function logger_log_full( $var, $level=LOG_DEBUG )
	{
		return logger_log( var_dump_human_full($var), $level );
	}
}