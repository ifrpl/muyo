<?php

ini_set('include_path',ini_get('include_path').PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'zend');

/**
 * @param      $path
 * @param null $env
 *
 * @return mixed|Zend_Config|Zend_Config_Ini|Zend_Config_Xml
 */
function getConfig($path, $env = null)
{
	require_once 'Lib/Config.php';
	$config = new Lib_Config($path);
	return $config->getConfig($env);
}

/**
 * @param Zend_Form_Element|Zend_Form $target
 * @param string                      $class
 */
function ifr_add_class($target, $class)
{
	$attrib  = $target->getAttrib('class');
	$classes = explode(' ', $attrib);
	if( false === array_search($class, $classes) )
	{
		$target->setAttrib('class', $attrib . ' ' . $class);
	}
}