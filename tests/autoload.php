<?php

require_once  __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

require_once realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR .'_.php';

define('APPLICATION_PATH',  __DIR__);
define('CONF_PATH',         '/etc/IFR/Main/tests/configs');

\IFR\Main\Lib\App::get()->loadFile(CONF_PATH . '/environment.php');