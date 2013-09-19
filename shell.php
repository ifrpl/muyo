<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/**
 * @return string
 */
function getShellTop()
{
	if(!debug_allow()) return null;
	return shell_exec("top -bcs -n 2 -p ".getmypid());
}