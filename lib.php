<?php
/**
 * @package lib\main
 */

ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.dirname(__FILE__));

require_once "arr.php";
require_once "cli.php";
require_once "database.php";
require_once "debug.php";
require_once "file_system.php";
require_once "locale.php";
require_once "logger.php";
require_once "misc.php";
require_once "net.php";
require_once "object.php";
require_once "shell.php";
require_once "string.php";
require_once "zend.php";