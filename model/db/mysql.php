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

			if($this->_ignoreChangeLog === false)
			{
				Space_Model_Log::changeLog(Space_Model_Log::TYPE_UPDATE, $this);
			}
		}
		else
		{
			unset($data[$pkey]);

			$query = $this->getDb();
			$query->insert($this->getTable(), $data);

			// dunno why below worked well, but now it broke
			$id = $this->getDb()->lastInsertId();
			$this->{$pkey} = $id;

			if($this->_ignoreChangeLog === false)
			{
				Space_Model_Log::changeLog(Space_Model_Log::TYPE_INSERT, $this);
			}
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
		Space_Model_Log::changeLog(Space_Model_Log::TYPE_DELETE, $this);

		if(is_null($this->{$this->_primaryKey}))
		{
			throw new Exception('Nothing to delete, id is empty');
		}
		$delete = $this->getDb();
		$rows = $delete->delete($this->getTable(), array($this->_primaryKey.' = ?' => $this->{$this->_primaryKey}));

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

		if( APPLICATION_ENV != 'development' )
		{
			$this->clearAfterLoad();
		}

		return $data;
	}

}