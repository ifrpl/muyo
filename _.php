<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)

if( !defined('ROOT_PATH') && !defined('NO_OVERRIDE') )
{
	define( 'ROOT_PATH', getcwd() );
}
ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.dirname(__FILE__));

require_once __DIR__.DIRECTORY_SEPARATOR.'loader.php';
loader_include_dir_recursive(__DIR__);

// Set default logger
logger_set();

debug_handler();