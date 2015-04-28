<?php

require_once  __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

require_once realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR .'_.php';

$envFilePath = "./configs/environment.php";
/** Init environment */
if( file_exists( "$envFilePath.local" ) )
{
	require_once "$envFilePath.local";
}
elseif( file_exists( $envFilePath ) )
{
	require_once $envFilePath;
}

define('APPLICATION_PATH', __DIR__);
 