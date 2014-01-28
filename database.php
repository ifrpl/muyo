<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/**
 * @param $sqlQuery
 * @param $pdoObject
 *
 * @return bool
 */
function pg_last_insert_id($sqlQuery, $pdoObject)
{
	// Checks if query is an insert and gets table name
	if( preg_match("/^INSERT[\t\n ]+INTO[\t\n ]+([a-z0-9\_\-]+)/is", $sqlQuery, $tablename) )
	{
		// Gets this table's last sequence value
		$query = "SELECT currval('" . $tablename[1] . "_id_seq') AS last_value";

		$temp_q_id = $pdoObject->prepare($query);
		$temp_q_id->execute();

		if($temp_q_id)
		{
			$temp_result = $temp_q_id->fetch(PDO::FETCH_ASSOC);
			return ( $temp_result ) ? $temp_result['last_value'] : false;
		}
	}

	return false;
}

/**
 * @param string|null $database
 *
 * @return array
 */
function mysql_tables_list($database=null)
{
    $tables = array();
    $sql = null === $database ? 'SHOW TABLES' : "SHOW TABLES FROM {$database};";
    $result = mysql_query($sql);
	if( false === $result )
	{
		return false;
	}
	else
	{
		while($table = mysql_fetch_row($result))
		{
			$tables[] = $table[0];
		}
		return $tables;
	}
}

/**
 * @param string $column
 * @return string
 */
function mysql_quote_column( $column )
{
	debug_enforce_type( $column, 'string' );
	return '`'.mysql_real_escape_string( $column ).'`';
}

/**
 * @return callable
 */
function mysql_quote_column_dg()
{
	return function()
	{
		return mysql_quote_column( func_get_arg(0) );
	};
}

/**
 * @param string $column
 * @return string
 */
function mysql_quote_table( $column )
{
	debug_enforce_type( $column, 'string' );
	return '`'.mysql_real_escape_string( $column ).'`';
}

/**
 * @return callable
 */
function mysql_quote_table_dg()
{
	return function()
	{
		return mysql_quote_table( func_get_arg(0) );
	};
}