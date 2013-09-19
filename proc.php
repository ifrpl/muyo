<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/**
 * @param string $command
 * @param array|null &$output
 * @param int|null &$retval
 * @return string
 */
function proc_exec($command,&$output=array(),&$retval=null)
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
	if( 0 !== $retval )
	{
		logger_log("Process returned error." . PHP_EOL
			. " * Cli: " . $command          . PHP_EOL
			. " * Return value: " . $retval  . PHP_EOL
			. " * Stderr: "                  . PHP_EOL
			. str_indent($stderr,1)          . PHP_EOL
			. " * Stdout: "                  . PHP_EOL
			. str_indent($stdout,1)          . PHP_EOL
		);
		debug_assert(false); // FIXME: should be enforce but i wont risk it now
	}

	$ol = count($output);
	return $ol > 0 ? $output[$ol-1] : '';
}