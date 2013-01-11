<?php

/**
 * @return string
 */
function getShellTop()
{
	if(!ifrShowDebugOutput()) return null;
	return shell_exec("top -bcs -n 2 -p ".getmypid());
}