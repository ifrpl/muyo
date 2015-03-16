<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


if( !function_exists('pcntl_async_call') )
{
	function pcntl_async_call($callable)
	{
		$pid = pcntl_fork();
		debug_enforce(-1 !== $pid, 'Fork failed');

		$is_parent = $pid !== 0;

		if( $is_parent )
		{
			return array(
				'finish' => function() use($pid) { pcntl_waitpid($pid,$status); return $status;  },
				'done' => function() use($pid) { return !posix_kill($pid, 0); }
			);
		}
		else
		{
			$callable();
			posix_kill(getmypid(), SIGKILL);
			exit();
		}
	}
}

if( !function_exists('async_call') && function_exists('pcntl_fork') )
{
	/**
	 * Run callable asynchronously (in another process).
	 *
	 * Example:
	 *  $child = async_call(function(){ writeln("Hello from child!"); });
	 *  while( !$child['done'] ) { writeln("Hurry up child, we have no time."); }
	 *  $child['finish'](); // to terminate child safely
	 *
	 * @param callable $callable
	 * @return array
	 */
	function async_call($callable)
	{
		return pcntl_async_call($callable);
	}
}

if( !function_exists('parallel_each') )
{
	if( function_exists('async_call') )
	{
		/**
		 * Warning: remember it's PHP. You need to use OS primitives for communicating with caller.
		 *
		 * @param array $array
		 * @param callable $callable
		 */
		function parallel_each($array,$callable)
		{
			$count = count($array);
			$cores = hw_core_get();
			$childs = array();
			if( $count <= $cores )
			{
				foreach($array as $k => $v)
				{
					$childs []= async_call(function()use($k,$v,$callable){ $callable($v,$k); });
				}
			}
			foreach(array_chunk($array, ceil($count / $cores), true) as $chunk)
			{
				$childs []= async_call(function() use($chunk,$callable)
				{
					foreach($chunk as $k => $v)
					{
						$callable($v,$k);
					}
				});
			}
			foreach($childs as $child)
			{
				$child['finish']();
			}
		}
	}
	else
	{
		function parallel_each($array,$callable)
		{
			array_walk($array,$callable);
		}
	}
}

$cli_format_error = function($cmd,$message,$stderr)
{
	$err = $stderr ? 'StdErr:'.PHP_EOL.str_indent($stderr,1).PHP_EOL : '';
	return "Message: $message".PHP_EOL
		.  "Command: $cmd".PHP_EOL
		.  $err;
};

if( !function_exists('parallel_exec') )
{
	/**
	 * @param array $commands
	 * @param callable $onerror ($command,$code,$stderr)
	 * @return array
	 */
	function parallel_exec($commands, $onerror = null)
	{
		$spec = array(
			0 => array('pipe','r'), //in
			1 => array('pipe','w'), //out
			2 => array('pipe','w'), //err
		);

		$errors = array();
		$ret = array();
		foreach(array_chunk($commands, hw_core_get(), true) as $chunk)
		{
			$processes = array();
			foreach($chunk as $key => $command)
			{
				$key = to_hash($key);
				$process = proc_open($command,$spec,$pipes);
				if( is_resource($process) )
				{
					fclose($pipes[0]);
					$processes[$key]= array(
						'cmd' => $command,
						'res' => $process,
						'out' => $pipes[1],
						'err' => $pipes[2],
					);
				}
				else
				{
					$processes[$key]= $command;
				}
			}
			while( !empty($processes) )
			{
				foreach($processes as $key => $v)
				{
					$resource = $v['res'];
					$cmd = $v['cmd'];
					if( is_resource($resource) )
					{
						$status = proc_get_status($resource);
						if( !$status['running'] )
						{
							$code = $status['exitcode'];

							$out = stream_get_contents($v['out']);
							fclose($v['out']);

							$err = stream_get_contents($v['err']);
							fclose($v['err']);

							proc_close($resource);
							unset($processes[$key]);

							if( 0 !== $code )
							{
								$errors[$key] = array(
									'error' => "Error code '$code'",
									'cmd' => $cmd,
									'err' => $err,
								);
							}
							else
							{
								$ret[$key] = $out;
							}
						}
					}
					else
					{
						unset($processes[$key]);
						$errors[$key] = array(
							'error' => "Could not open process",
							'cmd' => $cmd,
						);
					}
				}
			}
		}

		if( null !== $onerror )
		{
			foreach($errors as $error)
			{
				$onerror($error['cmd'],$error['error'],array_key_exists('err',$error)?$error['err']:null);
			}
		}
		else
		{
			$format_errors = function($errors)
			{
				$errors = array_map_val($errors, function($error)
				{
					global $cli_format_error;
					return $cli_format_error($error['cmd'],$error['error'],array_key_exists('err',$error)?$error['err']:null);
				});
				return implode(PHP_EOL.'==='.PHP_EOL,$errors);
			};
			debug_enforce( empty($errors), $format_errors($errors) );
		}
		return $ret;
	}
}