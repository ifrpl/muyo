<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


if( !function_exists('pg_last_insert_id') )
{
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
}

if( !function_exists('mysql_tables_list') )
{
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
}


if( !function_exists('mysql_connect_dg') )
{
	/**
	 * @param string|null $server
	 * @param string|null $username
	 * @param string|null $password
	 * @param bool $new_link
	 * @param int  $client_flags
	 * @return callable
	 */
	function mysql_connect_dg( $server=null, $username=null, $password=null, $new_link=false, $client_flags=0 )
	{
		return function()use( $server, $username, $password, $new_link, $client_flags )
		{
			if( null===$server )
			{
				$server = ini_get( "mysql.default_host" );
			}
			if( null===$username )
			{
				$username = ini_get( "mysql.default_user" );
			}
			if( null===$password )
			{
				$password = ini_get( "mysql.default_password" );
			}
			$ret = mysql_connect( $server, $username, $password, $new_link, $client_flags );
			return $ret;
		};
	}
}

if( !function_exists('mysql_close_dg') )
{
	/**
	 * @param resource|callable $conn_getter
	 * @return callable
	 */
	function mysql_close_dg( $conn_getter )
	{
		if( is_callable($conn_getter) )
		{
			return function()use( $conn_getter )
			{
				$args = func_get_args();
				$conn = call_user_func_array( $conn_getter, $args );
				return mysql_close( $conn );
			};
		}
		else
		{
			return function()use( $conn_getter )
			{
				return mysql_close( $conn_getter );
			};
		}
	}
}