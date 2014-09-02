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
    $nb = 0;

    $output = file_get_contents('/proc/cpuinfo');
    $lines = explode("\n", $output);

    foreach($lines as $line)
    {
        $tab = array_map(function($value){return trim($value);}, explode(':', $line));
        if(2 != count($tab) || 'processor' != $tab[0])
        {
            continue;
        }

        $nb++;
    }

	return $nb;
}