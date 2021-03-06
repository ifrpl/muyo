<?php

if( !class_exists('Lib_Model_Db') )
{
	require_once( implode( DIRECTORY_SEPARATOR, [__DIR__,'db.php'] ) );
}

/**
 * @package App
 * @subpackage Db
 *
 * @method Zend_Db_Adapter_Pdo_Mysql getDb()
 */
abstract class Lib_Model_Db_Mysql extends Lib_Model_Db
{
	const LOAD_ARRAY_MODE_NESTED_TABLE  = 0;
	const LOAD_ARRAY_MODE_NESTED_COLUMN = 1;
	const LOAD_ARRAY_MODE_RAW           = 2;

	/**
	 * @var Zend_Db_Select
	 */
	protected $_select;

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
			$where = [$pkey.' = ?' => $this->{$pkey}];
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

		$this->_onSave();

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
		$rows = $delete->delete($this->getTable(), [$this->_primaryKey.' = ?' => $this->{$this->_primaryKey}]);

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
			$q->columns(['*']);
		}
		elseif( !array_some( $this->getColumns(), function($arr)use($pkey){ return $pkey == zend_column_name($arr); } ) )
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

		$data = [];
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

		return $data;
	}

	/**
	 * @param string $expr
	 * @param string $bindAs
	 * @return $this
	 */
	public function setCount( $expr='1', $bindAs='count' )
	{
		return $this->setColumns([
			$bindAs => new Zend_Db_Expr("COUNT($expr)"),
		]);
	}

	/**
	 * @param string|null $expr
	 * @param string|null $bindAs
	 * @return $this
	 */
	public function countSet( $expr=null, &$bindAs=null )
	{
		if( $expr===null )
		{
			$expr = '1';
		}
		else
		{
			$this->prefixColumn( $this, $expr );
		}
		if( $bindAs===null )
		{
			$bindAs = 'count';
		}
		return $this->setCount( $expr, $bindAs );
	}

	/**
	 * @param string|Zend_Db_Expr $expr
	 * @param bool $distinct
	 * @param null|string &$bindAs
	 * @return $this
	 */
	public function maxSet( $expr, $distinct=false, &$bindAs=null )
	{
		$exprCopy = $expr;
		if( true===$this->prefixColumn( $this, $expr ) )
		{
			$bindAs = $exprCopy;
		}
		$expr = (string) $expr;
		if( $distinct )
		{
			$expr = str_prepend( 'DISTINCT ', $expr );
		}
		$expr = 'MAX('.$expr.')';
		return $this
			->setColumns( $bindAs===null ? $expr : "{$expr} AS {$bindAs}", $this->getAlias() )
		;
	}

	/**
	 * @return int
	 */
	public function count()
	{
		$ret = $this->getDb()->fetchOne($this->getSelect()->reset('columns')->columns('count(1)'));
		return intval($ret);
	}

	/**
	 * WARNING: proper string/expression escaping is missing
	 * @param array|Zend_Db_Expr|string $expr
	 * @param string|null $alias
	 * @param string|null $separator
	 * @param bool|null $distinct
	 * @param null|array|int|string|Zend_Db_Expr $order
	 * @return $this
	 */
	public function groupConcatSet( $expr, $alias=null, $separator=null, $distinct=null, $order=null )
	{
		debug_assert(
			is_string($expr) || is_array($expr) || (is_object($expr) && $expr instanceof Zend_Db_Expr),
			"Invalid expression value `".var_dump_human_compact($expr)."`"
		);
		debug_assert(
			is_null($alias) || is_string($alias),
			"Invalid alias value `".var_dump_human_compact($alias)."`"
		);
		debug_assert(
			is_null($separator) || is_string($separator),
			"Invalid separator value `".var_dump_human_compact($separator)."`"
		);
		debug_assert(
			is_null($distinct) || is_bool($distinct),
			"Invalid distinct value `".var_dump_human_compact($distinct)."`"
		);
		debug_assert(
			is_null($order) || is_array($order) || is_int($order) || is_string($order) || (is_object($order) && $order instanceof Zend_Db_Expr),
			"Invalid order value `".var_dump_human_compact($order)."`"
		);
		$convertToString = function($expr)
		{
			if( is_object($expr) )
			{
				$expr = strval($expr);
			}
			else
			{
				if( array_key_exists( $expr, $this->schemaColumnsGet() ) )
				{
					$expr = str_wrap($this->getAlias(),'`').'.'.str_wrap( $expr, '`' );
				}
				elseif( is_string($expr) )
				{
					$expr = str_wrap( $expr, '\'' );
				}
			}
			return $expr;
		};
		if( is_array($expr) )
		{
			$expr = array_chain(
				$expr,
				array_map_val_dg( tuple_get( 0, $convertToString ) ),
				array_implode_dg(',')
			);
		}
		else
		{
			$expr = $convertToString( strval($expr) );
		}
		if( $distinct!==null )
		{
			$expr = "DISTINCT {$expr}";
		}
		if( $order!==null )
		{
			if( is_array($order) )
			{
				$order = array_chain(
					$expr,
					array_map_val_dg( tuple_get( 0, $convertToString ) ),
					array_implode_dg(',')
				);
			}
			else
			{
				$order = $convertToString( strval($order) );
			}
			$expr = "{$expr} ORDER BY {$order}";
		}
		if( $separator!==null )
		{
			$expr = "{$expr} SEPARATOR '{$separator}'";
		}
		$expr = "GROUP_CONCAT({$expr})";
		if( $alias!==null )
		{
			$expr = [$alias=>$expr];
		}
		return $this->setColumns( $expr, $this->getAlias() );
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
	 * @return App_Model_Db_Mysql
	 */
	public function insertFrom($model)
	{
		$db = $this->getDb();
		$myCols = array_keys( $this->schemaColumnsGet() );
		$theirCols = $model->getColumnAliases();
		$columns = '('.implode( ',', array_map_val( array_intersect( $theirCols, $myCols ), $this->quoteColumnDg() ) ).')';
		$table = $this->quoteTable();
		$db->exec('INSERT INTO '.$table.' '.$columns.' '.$model->getSQL());
		$this->id = $this->getLastInsertId();
		if( $this->recordExists() )
		{
			return $this->filterById($this->id)->loadOne();
		}
		else
		{
			return $this;
		}
	}

	/**
	 * Load results as CSV-like file on database server.
	 * @param string $path
	 * @param string $fieldsTerminator
	 * @param string $fieldsEncloser
	 * @param string $linesTerminator
	 */
	public function loadFile( $path, $fieldsTerminator=',', $fieldsEncloser='"', $linesTerminator="\n" )
	{
		debug_enforce_string( $path );
		debug_enforce_string( $fieldsTerminator );
		debug_enforce_string( $fieldsEncloser );
		debug_enforce_string( $linesTerminator );
		$sql = $this->getSql();
		$db = $this->getDb();
		$path = $db->quote( $path, 'string' );
		$linesTerminator = $db->quote( $linesTerminator, 'string' );
		$fieldsEncloser = $db->quote( $fieldsEncloser, 'string' );
		$fieldsTerminator = $db->quote( $fieldsTerminator, 'string' );
		$db->exec(
			$sql."\n"
			. "INTO OUTFILE $path"
			. " FIELDS TERMINATED BY $fieldsTerminator"
			. " ENCLOSED BY $fieldsEncloser"
			. " LINES TERMINATED BY $linesTerminator"
		);
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

	/**
	 * @return array
	 */
	public function getColumns()
	{
		return $this->getSelect()->getPart('columns');
	}



	/**
	 * Return settings only for set columns
	 * @return array
	 */
	public function getColumnSettings($columns = null)
	{
		if(null == $columns)
		{
			$columns = array_map(function($descriptor)
				{
					return zend_column_name($descriptor);
				},
				$this->getColumns()
			);
		}

		$k = array_find_key($columns, function($v, $k){
				return $v == $this->getPrimaryKey();
			}
		);

		if(null !== $k)
		{
			unset($columns[$k]);
		}

		return  array_join($columns, $this->_settings, false);

	}

	/**
	 * @param bool $clear
	 * @param string $cols
	 * @return Zend_Db_Select
	 */
	public function getSelect($clear = false, $cols = '*')
	{
		if( is_null($this->_select) || $clear )
		{
			if( $cols === '*' )
			{
				$cols = array_keys($this->schemaColumnsGet());
			}
			$this->_select = $this->getDb()->select();
			$this->_select->from([$this->getAlias() => $this->getTable()], $cols);
		}
		return $this->_select;
	}

	/**
	 * @param Zend_Db_Select $select
	 * @return $this
	 * @override
	 */
	public function setSelect($select)
	{
		$this->_select = $select;
		return $this;
	}

	/**
	 * Clear part of select statement. Ex: columns, limit
	 *
	 * @param string $name
	 * @return $this
	 */
	public function clearPart($name)
	{
		$this->getSelect()->reset($name);
		return $this;
	}

	public function clearColumns( $clearPK=false )
	{
		$this->clearPart('columns');
		return $this;
	}

	/**
	 * @param array|string $cols
	 * @param string|null   $correlationName
	 * @return $this
	 */
	public function setColumns($cols = '*', $correlationName = null)
	{
		$this->getSelect()->columns($cols, $correlationName);
		if( $cols === '*' )
		{
			if( debug_assert(null === $correlationName || $correlationName === $this->getAlias(),"* pseudo-column detected. Removing every occurrence of it will give us more power.") )
			{
				$this->normalizeColumns();
			}
		}
		return $this;
	}

	/**
	 * @param string $cond
	 * @param mixed $value
	 * @param null|int $type
	 * @return $this
	 */
	public function setWhere($cond, $value = null, $type = null)
	{
		$this->getSelect()->where($cond, $value, $type);
		return $this;
	}

	/**
	 * @param string $cond
	 * @param mixed $value
	 * @param null|int $type
	 * @return $this
	 */
	public function setHaving($cond, $value = null, $type = null)
	{
		$this->getSelect()->having($cond, $value, $type);
		return $this;
	}

	/**
	 * @param null|int $count
	 * @param null|int $offset
	 * @return $this
	 */
	public function setLimit($count = null, $offset = null)
	{
		$this->getSelect()->limit($count, $offset);
		return $this;
	}

	/**
	 * @param  $page
	 * @param  $rowCount
	 * @return $this
	 */
	public function setLimitPage($page, $rowCount)
	{
		$this->getSelect()->limitPage($page, $rowCount);
		return $this;
	}

	/**
	 * @param array|string $spec
	 * @return $this
	 */
	public function setGroup($spec)
	{
		$this->getSelect()->group($spec);
		return $this;
	}

	/**
	 * @param string|array|Lib_Model $name
	 * @return array|mixed
	 */
	private function prepareTableForJoin($name)
	{
		$alias = null;

		if( is_array($name) )
		{
			$alias = key($name);
			$name  = current($name);
		}

		if( is_string($name) && class_exists($name) )
		{
			$name = new $name(null, false);
		}

		if( $name instanceOf Lib_Model_Db )
		{
			if(is_null($alias))
			{
				$alias = $name->getAlias();
			}

			$name = [$alias => $name->getTable()];
		}
		elseif( $alias )
		{
			$name = [$alias => $name];
		}
		return $name;
	}

	/**
	 * @param string|array|Lib_Model $name
	 * @param string $cond
	 * @param string|array $cols
	 * @param null|string $schema
	 * @return $this
	 */
	public function setJoin($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		$this->getSelect()->join($this->prepareTableForJoin($name), $cond, $cols, $schema);
		return $this;
	}

	/**
	 * @param string|array|Lib_Model $name
	 * @param string $cond
	 * @param string|array $cols
	 * @param null|string $schema
	 * @return $this
	 */
	public function setJoinLeft($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		$this->getSelect()->joinLeft($this->prepareTableForJoin($name), $cond, $cols, $schema);
		return $this;
	}

	/**
	 * @param string|array|Lib_Model $name
	 * @param string $cond
	 * @param string|array $cols
	 * @param null|string $schema
	 * @return $this
	 */
	public function setJoinRight($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		$this->getSelect()->joinRight($this->prepareTableForJoin($name), $cond, $cols, $schema);
		return $this;
	}

	/**
	 * @param string|array|Lib_Model $name
	 * @param string $cond
	 * @param string|array $cols
	 * @param null|string $schema
	 * @return $this
	 */
	public function setJoinInner($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		$this->getSelect()->joinInner($this->prepareTableForJoin($name), $cond, $cols, $schema);
		return $this;
	}

	/**
	 * @param bool $flag
	 * @return $this
	 */
	public function setDistinct($flag = true)
	{
		$this->getSelect()->distinct($flag);
		return $this;
	}

	/**
	 * @param string|array $order
	 * @return $this
	 */
	public function setOrderBy($order)
	{
		$this->getSelect()->order($order);
		return $this;
	}

	/**
	 * @param string|array $cond condition (if one param) or column name if more than one params
	 *
	 * @return $this
	 */
	public function filterBy($cond)
	{
		$select = $this->getSelect();
		$db = $this->getDb();
		$descriptors = $this->getColumns();
		$alias = $this->getAlias();

		if ( is_array($cond) )
		{
			foreach($cond as $col => $value)
			{
				if( array_find_key( $descriptors, zend_column_eq_dg( $alias, $col ) ) )
				{
					$col = "$alias.$col";
				}
				$col = $db->quoteIdentifier($col);
				if( is_array($value) )
				{
					$valueChar = '?';
					$cond2 = '=';

					if(isset($value['condition']) && isset($value['value']))
					{
						$cond2 = $value['condition'];
						$value = $value['value'];
						$valueChar = '?';
					}

					if( is_array($value) )
					{
						if( 0 === count($value) )
						{
							$select->where('TRUE=FALSE');
							return $this;
						}
						if(strpos($cond2, 'IN') === false)
						{
							$cond2 = 'IN';
						}
						$valueChar = '(?)';
					}

					$select->where($col.' '. $cond2 .' '.$valueChar, $value);
				}
				else
				{
					if( is_null($value) )
					{
						$select->where($col.' IS NULL');
					}
					elseif( $value instanceof Zend_Db_Expr )
					{
						$select->where($col.' '.$value);
					}
					else
					{
						$select->where($col.' = ?', $value);
					}
				}
			}
		}
		else
		{
			$select->where($cond);
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function filterFalse()
	{
		return $this->setWhere('1=0');
	}

	/**
	 * @param string|array $cond condition (if one param) or column name if more than one params
	 *
	 * @return $this
	 */
	public function filterNotBy($cond)
	{
		$select = $this->getSelect();

		if ( is_array($cond) )
		{
			foreach($cond as $col => $value)
			{
				if( is_array($value) )
				{
					$valueChar = '?';
					$cond2 = '!=';

					if(isset($value['condition']) && isset($value['value']))
					{
						$cond2 = $value['condition'];
						$value = $value['value'];
						$valueChar = '?';
					}

					if( is_array($value) )
					{
						if( 0 === count($value) )
						{
							continue;
						}
						$cond2 = 'NOT IN';
						$valueChar = '(?)';
					}

					$select->where($col.' '. $cond2 .' '.$valueChar, $value);
				}
				else
				{
					if( is_null($value) )
					{
						$select->where($col.' IS NOT NULL');
					}
					elseif( $value instanceof Zend_Db_Expr )
					{
						$select->where($col.' '.$value);
					}
					else
					{
						$select->where($col.' != ?', $value);
					}
				}
			}
		}
		else
		{
			$select->where($cond);
		}

		return $this;
	}

	/**
	 * @param int|array|Zend_Db_Expr $id
	 * @return $this
	 */
	public function filterNotById($id)
	{
		$alias = $this->getAlias();
		$key = $this->getPrimaryKey();
		return $this->filterNotBy(["{$alias}.{$key}"=>$id]);
	}

	/**
	 * @return $this
	 */
	public function groupById()
	{
		$alias = $this->getAlias();
		$key = $this->getPrimaryKey();
		return $this->setGroup("{$alias}.{$key}");
	}

	/**
	 * WARNING: do not use if don't know internals (partially implemented)
	 * Load model from SQL query to an array with joined columns as arrays
	 * @param mixed $q
	 * @param bool $collection
	 * @param int $mode defines how results should be structured
	 * @return array
	 * @throws Exception
	 * @fixme $q and $collection
	 */
	public function loadArray( $q=null, $collection=false, $mode=self::LOAD_ARRAY_MODE_NESTED_TABLE )
	{
		$alias = $this->getAlias();

		$descriptors = $this->getColumns();
		if( empty( $descriptors ) )
		{
			$this->setColumns(array_keys($this->schemaColumnsGet()), $alias);
			$descriptors = $this->getColumns();
		}

		if( !$collection )
		{
			$key_name = $this->getPrimaryKey();
			$key_idx = array_find_key( $descriptors, zend_column_eq_dg( $alias, $key_name ) );
			if( !debug_assert( !is_null($key_idx), "Key '$key_name' needs to be set to load hash-set of '$alias'") )
			{
				$this->setColumns( [$key_name] );
				$descriptors = $this->getColumns();
				$key_idx = array_find_key( $descriptors, zend_column_eq_dg( $alias, $key_name ) );
				debug_enforce( !is_null($key_idx) );
			}
		}
		else
		{
			$key_idx = null;
		}

		if( $q===null )
		{
			$q = $this->getSQL();
		}

		$db = $this->getDb();
		try
		{
			$rows = $db->fetchAll( $q, [], Zend_Db::FETCH_NUM );
		}
		catch( Exception $e )
		{
			throw new Exception('Error while loading: '.$e->getMessage().' | SQL: '.$this->getSQL());
		}

		if(self::LOAD_ARRAY_MODE_RAW == $mode)
		{
			return $rows;
		}

		$ret = [];
		foreach( $rows as $row )
		{
			$record = [];

			foreach( $row as $idx=>$column )
			{
				$descriptor = $descriptors[ $idx ];
				$colalias = strval( zend_column_name( $descriptor ) );


				if(self::LOAD_ARRAY_MODE_NESTED_TABLE == $mode)
				{
					$tblalias = zend_column_table( $descriptor );
					if( !array_key_exists( $tblalias, $record ) )
					{
						$record[ $tblalias ] = [];
					}

					$record[$tblalias][$colalias] = $column;
				}
				else
				{
					$record[$colalias] = $column;
				}
			}

			if( $collection )
			{
				$ret []= $record;
			}
			else
			{
				$key = $row[ $key_idx ];
				$ret[ $key ] = $record;
			}
		}
		return $ret;
	}

	/**
	 * @param string|null $q
	 * @param bool $collection
	 * @return Lib_Model_Set
	 * @throws Exception
	 */
	public function loadSet( $q=null, $collection=false )
	{
		$q = $this->getSelect();

		$pkey = $this->getPrimaryKey();

		if( count($q->getPart('columns')) == 0 )
		{
			$q->columns(['*']);
		}
		elseif( !$collection && !array_some( $this->getColumns(), function($arr)use($pkey){ return $pkey == zend_column_name($arr); } ) )
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

		$set = new Lib_Model_Set;
		$set->setResultSet($result);
		$set->setModel($this);

		return $set;
	}

	/**
	 * @return null|string
	 */
	public function getSQL()
	{
		return $this->getSelect()->assemble();
	}

	/**
	 * @param Lib_Model_db $model
	 * @param string &$column
	 * @return string
	 */
	private function prefixColumn($model, &$column)
	{
		if( false === strpos($column,'.') )
		{
			$column = $model->getAlias().'.'.$column;
			$prefixed = true;
		}
		else
		{
			$prefixed = false;
		}
		return $prefixed;
	}

	/**
	 * @param $callable
	 */
	private function mapPartWhere( $callable )
	{
		$select = $this->getSelect();
		$where=$select->getPart( Zend_Db_Select::WHERE );
		$select->reset( Zend_Db_Select::WHERE );
		foreach( $where as $string )
		{
			if(
				debug_assert(
					preg_match( '/^(OR|AND|) ?\((.*)\)$/', $string, $matches ),
					"Not recognized where expression `$string"
				)
			)
			{
				$prefix=$matches[ 1 ];
				$condition=$matches[ 2 ];
				$mapped = $callable( $condition, $prefix );
				if( is_array($mapped) )
				{
					list($condition,$prefix) = $mapped;
				}
				else
				{
					$condition = $mapped;
				}
				$select->where( $condition, null, null, $prefix==='OR'?false:true );
			}
		}
	}

	/**
	 * @return callable
	 */
	private function addAliasToConditionDg()
	{
		$thisColumns = array_keys( $this->schemaColumnsGet() );
		$thisAlias = $this->getAlias();
		return function( $condition )use($thisColumns,$thisAlias)
		{
			if( preg_match( '/^[`]?([a-zA-Z0-9_]+)[`]\.[`]?([a-zA-Z0-9_]+)[`]/', $condition, $matches ) )
			{ // table.alias
				$ret = $condition;
			}
			elseif( preg_match( '/^[`]?([a-zA-Z0-9_]+)[`]?([^.].*)$/', $condition, $matches ) )
			{ // table
				$columnName=$matches[ 1 ];
				$else = $matches[ 2 ];
				if( array_contains( $thisColumns, $columnName ) )
				{ // assume column that needs to be prefixed
					$ret = "`$thisAlias`.`$columnName`{$else}";
				}
				else
				{ // assume not a column name
					$ret = $condition;
				}
			}
			else
			{ // assume has alias
				$ret = $condition;
			}
			return $ret;
		};
	}

	/**
	 * WARNING: Only columns copying supported.
	 * @param Lib_Model_Db_Mysql $model
	 * @param string|null $thisKeyCol may contain table prefix or not
	 * @param string|null $thatKeyCol may contain table prefix or not
	 * @param string $conditions ['and' => '{this}.column={that}.column' ]
	 * @return Lib_Model_Db_Mysql
	 */
	public function joinTo($model,$thisKeyCol,$thatKeyCol=null,$conditions='')
	{
		if( $thatKeyCol===null )
		{
			debug_assert( $thisKeyCol!==null );
			$thatKeyCol = $model->getPrimaryKey();
		}
		elseif( $thisKeyCol===null )
		{
			debug_assert( $thatKeyCol!==null );
			$thisKeyCol = $this->getPrimaryKey();
		}

		$this->prefixColumn($model, $thatKeyCol);
		$this->prefixColumn($this, $thisKeyCol);

		$conditions = str_replace('{that}',$model->getAlias(),$conditions);
		$conditions = str_replace('{this}',$this->getAlias(),$conditions);

		$this->mapPartWhere( $this->addAliasToConditionDg() );
		$model->mapPartWhere( $model->addAliasToConditionDg() );

		$this->setJoinLeft($model, "{$thisKeyCol}={$thatKeyCol} ".$conditions, '');
		$model->settingsJoin($this);

		foreach( $model->getColumns() as $descriptor )
		{
			$table = $descriptor[0];
			$column = $descriptor[1];
			$alias = 3===count($descriptor) ? $descriptor[2] : null;

			if( null !== $alias )
			{
				$this->setColumns([$alias=>$column],$table);
			}
			else
			{
				$this->setColumns($column,$table);
			}
		};
		$model->setSelect(clone $this->getSelect());
		return $model;
	}

	/**
	 * WARNING: Only columns copying supported.
	 * @param Lib_Model_Db_Mysql $model
	 * @param string|null $thisKeyCol may contain table prefix or not
	 * @param string|null $thatKeyCol may contain table prefix or not
	 * @param string $conditions
	 * @return $this
	 */
	public function joinFrom($model, $thisKeyCol, $thatKeyCol=null, $conditions='')
	{
		if( $thatKeyCol===null )
		{
			debug_assert( $thisKeyCol!==null );
			$thatKeyCol = $model->getPrimaryKey();
		}
		elseif( $thisKeyCol===null )
		{
			debug_assert( $thatKeyCol!==null );
			$thisKeyCol = $this->getPrimaryKey();
		}

		$this->prefixColumn($model, $thatKeyCol);
		$this->prefixColumn($this, $thisKeyCol);

		$conditions = str_replace('{that}', $model->getAlias(), $conditions);
		$conditions = str_replace('{this}', $this->getAlias(), $conditions);

		$this->mapPartWhere( $this->addAliasToConditionDg() );
		$model->mapPartWhere( $model->addAliasToConditionDg() );

		$thisFrom = $this->_select->getPart(Zend_Db_Select::FROM);
		$modelColumns = array_chain(
			$model->_select->getPart(Zend_Db_Select::COLUMNS),
			array_group_dg( array_get_dg(return_dg(0)) ),
			array_map_val_dg(
				array_chain_dg(
					array_map_val_dg(
						function($descriptor)
						{
							return null===$descriptor[2]
								? $descriptor[1]
								: [ $descriptor[2] => $descriptor[1] ]
							;
						}
					),
					function($columns){
						$outArray = [];
						array_map_val($columns, function($column)use(&$outArray){
							if(is_array($column))
							{
								array_map_val($column, function($column, $alias)use(&$outArray){
									$outArray[$alias] = $column;
								});
							}
							else
							{
								$outArray[] = $column;
							}
						});
						
						return $outArray;
					}
				)
			)
		);

		array_each(
			$model->_select->getPart(Zend_Db_Select::FROM),
			function( $descriptor, $alias )use($modelColumns,$thisFrom,$model,$thisKeyCol,$thatKeyCol,$conditions)
			{
				debug_enforce(
					!array_key_exists( $alias, $thisFrom ),
					"Alias `{$alias}` already used for table `{$descriptor['tableName']}`"
				);

				switch( $descriptor['joinType'] )
				{
					case Zend_Db_Select::FROM:
						$this->_select->joinLeft(
							[$model->getAlias()=>$model->getTable()],
							"{$thisKeyCol}={$thatKeyCol} ".$conditions,
							array_key_exists( $alias, $modelColumns ) ? $modelColumns[ $alias ] : [],
							$descriptor[ 'schema' ]
						);
					break;
					case Zend_Db_Select::INNER_JOIN:
						$this->_select->joinInner(
							[$alias=>$descriptor['tableName']],
							$descriptor['joinCondition'],
							array_key_exists( $alias, $modelColumns ) ? $modelColumns[ $alias ] : [],
							$descriptor['schema']
						);
					break;
					case Zend_Db_Select::LEFT_JOIN:
						$this->_select->joinLeft(
							[$alias=>$descriptor['tableName']],
							$descriptor['joinCondition'],
							array_key_exists( $alias, $modelColumns ) ? $modelColumns[ $alias ] : [],
							$descriptor['schema']
						);
					break;
					case Zend_Db_Select::RIGHT_JOIN:
						$this->_select->joinRight(
							[$alias=>$descriptor['tableName']],
							$descriptor['joinCondition'],
							array_key_exists( $alias, $modelColumns ) ? $modelColumns[ $alias ] : [],
							$descriptor['schema']
						);
					break;
					case Zend_Db_Select::FULL_JOIN:
						$this->_select->joinFull(
							[$alias=>$descriptor['tableName']],
							$descriptor['joinCondition'],
							array_key_exists( $alias, $modelColumns ) ? $modelColumns[ $alias ] : [],
							$descriptor['schema']
						);
					break;
					case Zend_Db_Select::CROSS_JOIN:
						$this->_select->joinCross(
							[$alias=>$descriptor['tableName']],
							$descriptor['joinCondition'],
							array_key_exists( $alias, $modelColumns ) ? $modelColumns[ $alias ] : [],
							$descriptor['schema']
						);
					break;
					case Zend_Db_Select::NATURAL_JOIN:
						$this->_select->joinNatural(
							[$alias=>$descriptor['tableName']],
							$descriptor['joinCondition'],
							array_key_exists( $alias, $modelColumns ) ? $modelColumns[ $alias ] : [],
							$descriptor['schema']
						);
					break;
					default:
						debug_assert( false, "Unknown join type ".var_dump_human_compact($descriptor['joinType']));
					break;
				}
			}
		);

		$this->settingsJoin($model);

		return $this;
	}

	/**
	 * @deprecated please use getListBy instead
	 * @param array $cond
	 * @return array
	 */
	public function findBy($cond)
	{
		debug_assert( false, 'Function findBy is scheduled for deletion, replace by Model::getListBy($conditions)' );
		return static::getListBy( $cond );
	}

	/**
	 * Return one of row from DB or new row with data from params
	 * @deprecated please use getBy instead
	 * @see getBy
	 * @param array $conditions
	 * @return $this
	 */
	public function findOneBy($conditions)
	{
		debug_assert( false, 'Function findOneBy is scheduled for deletion, replace with Model::getBy($conditions)' );
		$instance = static::getBy( $conditions, array_keys($this->schemaColumnsGet()) );
		$this->fromArray( $instance->toArray() );
		$this->changedColumnsReset();
		return $this;
	}

	/**
	 * Return row object for current id
	 *
	 * @return Zend_Db_Table_Row|null
	 * @deprecated
	 */
	public function getRow()
	{
		debug_assert( false, 'Function getRow is scheduled for deletion, replace with Model::getById( $id )' );

		$key = $this->getPrimaryKey();
		if( null !== $key )
		{
			$ret = static::getById( $key );
		}
		else
		{
			$ret = null;
		}
		return $ret;
	}

	/**
	 * @param string $term
	 * @param array $eqCol
	 * @param array $likeCol
	 * @param array $additionalSelectCol
	 * @return $this
	 */
	public function buildSearchCondition(
		$term,
		array $eqCol = array(),
		array $likeCol = array(),
		array $additionalSelectCol = array()
	)
	{
		$collate = 'utf8_general_ci';
		$mappedTerm = str_map($term,function($char)
		{
			return ctype_alnum($char) ? $char : ' ';
		});
		$db = $this->getDb();
		$firstOne = true;
		$where = array_chain( explode(' ', $mappedTerm),
			array_filter_key_dg( not_dg(empty_dg()) ),
			array_map_val_dg( function( $termPart )use($db,$likeCol,$eqCol,$collate,&$firstOne)
			{
				if($firstOne)
				{
					$t1 = $db->quote( "$termPart%", 'string' );
					$firstOne = false;
				} else
				{
					$t1 = $db->quote( "%$termPart%", 'string' );
				}
				$t2 = $db->quote($termPart,'string');
				$whereLike = array_map_val($likeCol, function($column)use($collate,$termPart,$t1)
				{
					return "$column COLLATE $collate LIKE $t1";
				});
				$whereEq = array_map_val($eqCol, function($column)use($termPart,$t2)
				{
					return "$column = $t2";
				});
				$where = array_merge( $whereLike, $whereEq );
				return '( '.implode( ' OR ', $where ).' )';
			} )
		);

		$this
			->setColumns( array_merge( $eqCol, $likeCol, $additionalSelectCol ) )
			->setWhere( implode(" AND ", $where) );

		return $this;
	}

}