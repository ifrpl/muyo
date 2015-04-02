<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


if( !function_exists('getShellTop') )
{
	/**
	 * @return null|string
	 */
	function getShellTop()
	{
		if(!debug_allow()) return null;
		return shell_exec("top -bcs -n 2 -p ".getmypid());
	}
}

if( !function_exists('getShellPs') )
{
	/**
	 * @param bool $showHeader
	 * @param bool $logFile
	 * @return null|string
	 */
	function getShellPs($showHeader=false, $logFile=false)
	{
	  if(!debug_allow()) return null;
	  $h = ($showHeader === true)?'':'h';
	  $log = ($logFile === false)?'':' >> '.$logFile;
	  return shell_exec('ps '.$h.'p '.getmypid().' o time,pcpu,pmem,rss,lastcpu'.$log);
	}
}