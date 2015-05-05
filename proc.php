<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


if( !function_exists('proc_exec') )
{
	/**
	 * @param string $command
	 * @param array|null &$output
	 * @param int|null &$retval
	 * @return string
	 */
	function proc_exec($command, &$output=array(), &$retval=null)
	{
		$descriptors = array(
			0 => array("pipe","r"),
			1 => array("pipe","w"),
			2 => array("pipe","w"),
		);
		$res = proc_open($command, $descriptors, $pipes);

		fclose($pipes[0]);
		$stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		$output = explode(PHP_EOL,$stdout);

		$retval = proc_close($res);
        debug_assert(0 === $retval, implode(PHP_EOL, [
            "Process returned error.",
            " * Command: " . $command,
            " * Return value: " . $retval,
            " * Stderr: ",
            str_indent($stderr,1),
            " * Stdout: ",
            str_indent($stdout,1)
        ])); // FIXME: should be enforce but i wont risk it now


		$ol = count($output);
		return $ol > 0 ? $output[$ol-1] : '';
	}
}

if( !function_exists('proc_exec_dg') )
{
	/**
	 * @param string|callable $command_getter
	 * @param array|callable $output_setter
	 * @param array|callable $retval_setter
	 * @return callable
	 */
	function proc_exec_dg($command_getter, &$output_setter=array(), &$retval_setter=null )
	{
		return function()use( $command_getter, &$output_setter, &$retval_setter )
		{
			$args = func_get_args();
			if( is_callable($command_getter) )
			{
				$command = call_user_func_array( $command_getter, $args );
			}
			else
			{
				$command = $command_getter;
			}
			$ret = proc_exec( $command, $output, $retval );
			if( is_callable($output_setter) )
			{
				$output_setter( $output );
			}
			else
			{
				$output_setter = $output;
			}
			if( is_callable($retval_setter) )
			{
				$retval_setter( $retval );
			}
			else
			{
				$retval_setter = $retval;
			}
			return $ret;
		};
	}
}