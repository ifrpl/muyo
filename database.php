<?php

namespace database;

/**
 * @param $sqlQuery
 * @param $pdoObject
 *
 * @return bool
 */
function pgsqlLastInsertId($sqlQuery, $pdoObject)
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