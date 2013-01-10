<?php

ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.dirname(__FILE__));

defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'development');

defined('ROOT_PATH') || define('ROOT_PATH', realpath(dirname(__FILE__) . '/'));

use arr;
use cli;
use database;
use debug;
use file_system;
use locale;
use misc;
use net;
use object;
use shell;
use string;
use zend;