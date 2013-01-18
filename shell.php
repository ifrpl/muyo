<?php

/**
 * @return string
 */
function getShellTop()
{
	if(!debug_allow()) return null;
	return shell_exec("top -bcs -n 2 -p ".getmypid());
}