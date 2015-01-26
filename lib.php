<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)

/**
 * @package lib\main
 */

ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.dirname(__FILE__));

require_once "arr.php";
require_once "cli.php";
require_once "database.php";
require_once "misc.php";
require_once "debug.php";
require_once "file_system.php";
require_once "locale.php";
require_once "logger.php";
require_once "net.php";
require_once "hw.php";
require_once "proc.php";
require_once "parallel.php";
require_once "object.php";
require_once "shell.php";
require_once "string.php";
require_once "html.php";
require_once "bool.php";

require_once "model".DIRECTORY_SEPARATOR."_.php";
require_once "object".DIRECTORY_SEPARATOR."_.php";
require_once "zend.php";
require_once "wkhtmltox.php";