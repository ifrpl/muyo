<?

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
	debug_assert(0 === $retval,$stderr); // FIXME: should be enforce but i wont risk it now

	$ol = count($output);
	return $ol > 0 ? $output[$ol-1] : '';
}