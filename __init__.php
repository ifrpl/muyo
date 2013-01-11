<?php
namespace ifr\main
{
	ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . dirname(__FILE__));
}

namespace ifr\main\arr
{
	require_once "arr.php";
}
namespace ifr\main\cli
{
	require_once "cli.php";
}
namespace ifr\main\database
{
	require_once "database.php";
}
namespace ifr\main\debug
{
	require_once "debug.php";
}
namespace ifr\main\file_system
{
	require_once "file_system.php";
}
namespace ifr\main\locale
{
	require_once "locale.php";
}
namespace ifr\main\misc
{
	require_once "misc.php";
}
namespace ifr\main\net
{
	require_once "net.php";
}
namespace ifr\main\object
{
	require_once "object.php";
}
namespace ifr\main\shell
{
	require_once "shell.php";
}
namespace ifr\main\string
{
	require_once "string.php";
}
namespace ifr\main\zend
{
	require_once "zend.php";
}