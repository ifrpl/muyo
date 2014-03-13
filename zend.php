<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


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
 * @param Zend_Form_Element|Zend_Form|array $target
 * @param string                            $class
 */
function ifr_add_class(&$target, $class)
{
	if( !is_array($target) )
	{
		$attrib  = $target->getAttrib('class');
		$classes = explode(' ', $attrib);
	}
	else
	{
		$attrib = array_key_exists('class',$target) ? $target['class'] : '';
		$classes = is_array($attrib) ? $attrib : explode(' ',$attrib);
	}
	if( !array_contains($classes,$class) )
	{
		if( !is_array($target) )
		{
			$target->setAttrib('class', $attrib . ' ' . $class);
		}
		else
		{
			$classes []= $class;
			$target['class'] = implode(' ',$classes);
		}
	}
}

/**
 * Returns comparator for zend column $tableAlias AND $columnName
 * @param string|null $tableAlias
 * @param string|null $columnName
 * @return callable $zendColumnExpression => $isMatching
 */
function zend_column_eq_dg($tableAlias, $columnName)
{
	return function($descriptor)use($columnName,$tableAlias)
	{
		$colname = null !== $descriptor[2] ? $descriptor[2] : $descriptor[1];
		$tblalias = $descriptor[0];
		return $tblalias === $tableAlias && $colname === $columnName;
	};
}

/**
 * @param array $column column descriptor
 * @return string table name
 * @see Lib_Model_Db_Mysql::getColumns
 */
function zend_column_table($column)
{
	return $column[0];
}

/**
 * @param array $column column descriptor
 * @return string column name
 * @see Lib_Model_Db_Mysql::getColumns
 */
function zend_column_name( $column )
{
	$colname = null!==$column[2] ? $column[2] : $column[1];
	return $colname;
}