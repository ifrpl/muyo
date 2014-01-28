<?php

if( !class_exists('Lib_Model_Db') )
{
	require_once( implode( DIRECTORY_SEPARATOR, array(__DIR__,'db.php') ) );
}

/**
 * @package App
 * @subpackage Db
 *
 * @method Zend_Db_Adapter_Pdo_Mysql getDb()
 */
abstract class Lib_Model_Db_Mysql extends Lib_Model_Db
{

	/**
	 * @return $this
	 */
	public function save()
	{
		$pkey = $this->getPrimaryKey();
		$data = $this->serializeContent();
		if(isset($data[$pkey]) && !empty($data[$pkey]))
		{
			$query = $this->getDb();
			$where = array($pkey.' = ?' => $this->{$pkey});
			$query->update($this->getTable(), $data, $where);

			$this->_onUpdate();
		}
		else
		{
			unset($data[$pkey]);

			$query = $this->getDb();
			$query->insert($this->getTable(), $data);

			// dunno why below worked well, but now it broke
			$id = $this->getDb()->lastInsertId();
			$this->{$pkey} = $id;

			$this->_onInsert();
		}

		$this->changedColumnsReset();

		return $this;
	}

	/**
	 * @throws Exception
	 * @return bool
	 */
	public function delete()
	{
		if(is_null($this->{$this->_primaryKey}))
		{
			throw new Exception('Nothing to delete, id is empty');
		}
		$delete = $this->getDb();
		$rows = $delete->delete($this->getTable(), array($this->_primaryKey.' = ?' => $this->{$this->_primaryKey}));

		$this->_onDelete();

		if($rows > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @param bool $collection do not index by id
	 * @return array[static]
	 * @throws Exception
	 */
	public function load($q = null, $collection = false)
	{
		if( is_null($q) )
		{
			$q = $this->getSelect();
		}

		$pkey = $this->getPrimaryKey();

		if( count($q->getPart('columns')) == 0 )
		{
			$q->columns(array('*'));
		}
		elseif( !__($this->getColumns())->any(function($arr)use($pkey){ return $arr[2]===$pkey; }) )
		{
			$this->setColumns($pkey);
		}

		$db = $this->getDb();
		$this->preLoad();
		try
		{
			$result = $db->fetchAll($q);
		}
		catch( Exception $e )
		{
			throw new Exception('Error while loading: '.$e->getMessage().' | SQL: '.$q->assemble());
		}
		$this->postLoad();

		$data = array();
		foreach( $result as $row )
		{
			$obj = $this->modelFactory($row);
			$obj->changedColumnsReset();

			if( !$collection && $obj->id )
			{
				$data[$obj->id] = $obj;
			}
			else
			{
				$data[] = $obj;
			}
		}

		if( getCurrentEnv() != 'development' )
		{
			$this->clearAfterLoad();
		}

		return $data;
	}

	/**
	 * @param null $table
	 * @param null $pkey
	 * @return int
	 */
	public function getLastInsertId( $table=null, $pkey=null )
	{
		if( null === $table )
		{
			$table = $this->getTable();
		}
		if( null === $pkey )
		{
			$pkey = $this->getPrimaryKey();
		}
		return $this->getDb()->lastInsertId( $table, $pkey );
	}

	/**
	 * Inserts to table from different query.
	 * Warning: silently discards remote aliases if not existing as local column.
	 * @param App_Model_Db_Mysql $model
	 * @return int
	 */
	public function insertFrom($model)
	{
		$db = $this->getDb();
		$myCols = array_keys( $this->schemaColumnsGet() );
		$theirCols = $model->getColumnAliases();
		$columns = '('.implode( ',', array_map_val( array_intersect( $theirCols, $myCols ), $this->quoteColumnDg() ) ).')';
		$table = $this->quoteTable();
		$db->exec('INSERT INTO '.$table.' '.$columns.' '.$model->getSQL());
		return $this->getLastInsertId();
	}

	/**
	 * @param string|null $name
	 * @return string
	 */
	public function quoteTable( $name=null )
	{
		if( null === $name )
		{
			$name = $this->getTable();
		}
		return $this->getDb()->quoteTableAs( $name );
	}

	/**
	 * @return callable
	 */
	public function quoteTableDg()
	{
		$t = $this;
		return function()use($t)
		{
			$name = func_get_arg(0);
			return $t->quoteTable( $name );
		};
	}

	/**
	 * @param string $column
	 * @return string
	 */
	public function quoteColumn( $column )
	{
		return $this->getDb()->quoteColumnAs( $column, null );
	}

	/**
	 * @return callable
	 */
	public function quoteColumnDg()
	{
		$t = $this;
		return function()use($t)
		{
			$name = func_get_arg(0);
			return $t->quoteColumn( $name );
		};
	}

}