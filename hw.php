<?php

/**
 * @return int
 */
function hw_core_get()
{
	return intval(exec("cat /proc/cpuinfo | grep processor | wc -l"));
}