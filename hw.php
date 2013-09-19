<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/**
 * @return int
 */
function hw_core_get()
{
	return intval(exec("cat /proc/cpuinfo | grep processor | wc -l"));
}