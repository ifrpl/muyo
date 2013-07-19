<?

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

if( function_exists('pcntl_fork') )
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