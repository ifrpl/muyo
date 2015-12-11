<?php

if( !class_exists( 'Lib_Model' ) )
{
	require_once( implode( DIRECTORY_SEPARATOR, [__DIR__, '..','model.php'] ) );
}

/**
 * @package App
 *
 * @method static Lib_Model_Db getByColumn($value) returns loaded row, that has "column" matching $value
 * @method static array getListByColumn($value) returns all rows, that has "column" matching $value
 * @method static array getListById($id)
 */
abstract class Lib_Model_Db extends Lib_Model
{
	protected $_table;
	protected $_primaryKey = 'id';
	protected $_alias;

	const LOAD_ARRAY_MODE_NESTED_TABLE  = 0;
	const LOAD_ARRAY_MODE_NESTED_COLUMN = 1;
	const LOAD_ARRAY_MODE_RAW           = 2;


	/** @return mixed */
	abstract public function getDb();
	/** @return array [ [$tableAlias,$columnValueOrName,$columnAliasOrNull], ... ] */
	abstract public function getColumns();

	abstract public function save();
	abstract public function delete();
	abstract public function load($q = null, $collection = false);

	/**
	 * @param mixed $q
	 * @param bool  $collection
	 * @param int   $mode
	 * @return array
	 */
	abstract public function loadArray( $q=null, $collection=false, $mode=self::LOAD_ARRAY_MODE_NESTED_TABLE );

	/**
	 * @return Lib_Model_Set
	 */
	abstract public function loadSet();

	protected function _onSave(){}
	protected function _onUpdate(){}
	protected function _onInsert(){}
	protected function _onDelete(){}

	/**
	 * @param array|string $cols comma-separated columns list, *, or array of columns to read during query
	 * @param null|string $correlationName
	 * @return Lib_Model_Db
	 */
	abstract public function setColumns($cols = '*', $correlationName = null);
	abstract public function clearColumns($clearPK = false);

	/**
	 * @param string|array $cond condition (if one param) or column name if more than one params
	 * @return $this
	 */
	abstract public function filterBy($cond);

	/**
	 * @return $this
	 */
	abstract public function filterFalse();

	/** @return int */
	abstract public function count();
	/** @return string */
	abstract public function getSQL();

	/**
	 * @param mixed $select
	 * @return $this
	 */
	abstract public function setSelect($select);



	/**
	 * @param array|int|null $options
	 */
	public function __construct($options = null, $init = true)
	{
		if( empty($this->_alias) )
		{
			$this->_alias = array_chain(
				get_class($this),
				str_replace_dg('_',''),
				str_replace_dg('\\',''),
				strtolower_dg()
			);
		}

		parent::__construct($options, $init);

		$this->normalizeColumns();
	}

	/**
	 * Sets "name" column from this table for SELECTing, and being visible on grid/forms
	 *
	 * @param string $name
	 * @param string|null $alias
	 * @return $this
	 */
	public function columnSet($name,&$alias=null)
	{
		if( is_null($alias) )
		{
			$alias = $name;
			$this->setColumns($name, $this->getAlias());
		}
		else
		{
			$this->setColumns([$alias=>$name], $this->getAlias());
		}
		$this->addSetting($alias,self::settingDefaultGet($name));
		return $this;
	}

	/**
	 * Replace dreaded '*' with all columns
	 */
	public function normalizeColumns()
	{
		$model_alias = $this->getAlias();
		$ret = [];

		foreach( $this->getColumns() as $descriptor )
		{
			$alias = $descriptor[0];
			if( $alias === $model_alias )
			{
				if( null === $descriptor[2] && $descriptor[1] === '*' )
				{
					foreach( array_keys($this->schemaColumnsGet()) as $column_name )
					{
						$ret []= [$alias, $column_name, null];
					}
				}
				else
				{
					$ret []= $descriptor;
				}
			}
			else
			{
				$ret []= $descriptor;
			}
		}

		$this->resetColumns( $ret );
	}

	/**
	 * @param array $newColumns [[$tableAlias,$columnValue,$columnAlias],..]
	 * @return $this
	 */
	public function resetColumns($newColumns)
	{
		$this->clearColumns();

		foreach( $newColumns as $descriptor )
		{
			if ( $descriptor[2] )
				$this->setColumns([$descriptor[2] => $descriptor[1]], $descriptor[0]);
			else
				$this->setColumns($descriptor[1], $descriptor[0]);
		}

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	protected function isColumnSetLocally($name)
	{
		$columns = $this->getColumns();
		return array_contains(
			$columns,
			function($descriptor) use ($name)
			{
				$alias = $descriptor[2];
				$column = $descriptor[1];
				return $name === (null === $alias ? $column : $alias);
			}
		);
	}

	/**
	 * Returns currently set alias
	 * @return string
	 */
	public function getAlias()
	{
		return $this->_alias;
	}

	/**
	 * Sets alias to be used for setting columns in the future.
	 * @param string $value
	 * @return $this
	 * @see aliasSet version that replaces currently set columns
	 */
	public function setAlias($value)
	{
		$this->_alias = $value;
		return $this;
	}

	/**
	 * Replaces current alias with different one.
	 * @param string $value
	 * @return $this
	 * @see setAlias version that do not replaces currently set columns
	 */
	public function aliasSet( $value )
	{
		$columns = $this->getColumns();
		$this->aliasGet( $oldValue );
		foreach( $columns as &$column )
		{
			$tableAlias = $column[0];
			if( $tableAlias === $oldValue )
			{
				$column[0] = $value;
			}
		}

		$select = $this->getSelect();
		$from = $select->getPart( Zend_Db_Select::FROM );
		$select->reset( Zend_Db_Select::FROM );
		foreach( $from as $tableAlias => $descriptor )
		{
			if( $tableAlias === $oldValue )
			{
				unset($from[$oldValue]);
				$from[$value] = $descriptor;
			}
		}
		$select->from( [ $value => $this->getTable() ], [] );

		return $this
			->setAlias( $value )
			->resetColumns( $columns )
		;
	}

	/**
	 * Returns currently set table alias.
	 * @param &$ret
	 * @return $this
	 * @see aliasSet complementary setter
	 */
	public function aliasGet( &$ret )
	{
		$ret = $this->getAlias();
		return $this;
	}

	/**
	 * @throws Exception
	 * @return string
	 */
	public function getTable()
	{
		$model = get_called_class();
		debug_enforce( !is_null($this->_table), "Table name for model $model is empty" );
		return $this->_table;
	}

	/**
	 *  Delete records by specified conditions.
	 *
	 * @param array $condition array of conditions
	 * @return bool deleted?
	 *
	 * @todo Update logging to handle batches, then we can implement this function properly
	 */
	public function deleteBy($condition = null)
	{
		if( !is_null($condition) )
		{
			$this->filterBy($condition);
		}
		$matched = $this->load();
		array_each(
			$matched,
			function($model)
			{ /** @var Lib_Model_Db $model */
				$model->delete();
			}
		);
		return !empty($matched);
	}

	/**
	 * @param int|array|Zend_Db_Expr $id
	 *
	 * @return $this
	 */
	public function filterById($id)
	{
		$alias = $this->getAlias();
		$key = $this->getPrimaryKey();
		return $this->filterBy(["{$alias}.{$key}"=>$id]);
	}

	/**
	 * @static
	 * @param bool $clearColumns
	 * @deprecated please use find instead
	 * @see find
	 *
	 * @return static
	 */
	public static function get($clearColumns = false)
	{
		/** @var Lib_Model_Db $ret */
		$ret = new static();
		if ($clearColumns)
		{
			$ret->clearColumns();
		}
		else
		{
			$ret->normalizeColumns();
		}
		return $ret;
	}

	/**
	 * @param int|array|MongoID $id
	 *
	 * @return static
	 */
	public static function findById($id)
	{
		return static::find()->filterById($id);
	}

	/**
	 * @static
	 * @param bool $resetSettings
	 * @return static
	 */
	public static function find($resetSettings=false)
	{
		/** @var Lib_Model_Db $ret */
		$ret = parent::find($resetSettings);
		$ret->clearColumns();
		return $ret;
	}

	/**
	 * @static
	 * @param array $conditions
	 * @param array|callable|null $constructor
	 *
	 * @return static
	 */
	public static function getBy($conditions, $constructor=null)
	{
		$ret = self::getListBy($conditions,$constructor);
		$count = count($ret);
		if ($count === 0)
		{
			return static::find();
		}
		else
		{
			debug_assert($count === 1, 'getBy expects single or no result, but `'.$count.'` resulted.');
			return array_shift($ret);
		}
	}

	/**
	 * @param int $id
	 * @param array|callable|null $constructor
	 * @return static
	 */
	public static function getById( $id, $constructor=null )
	{
		$dummy = static::find();
		return static::getBy(
			[$dummy->getPrimaryKey()=>$id],
			$constructor
		);
	}

	/**
	 * @static
	 * @param array $conditions
	 * @param array|callable|null $constructor
	 *
	 * @return array
	 */
	public static function getListBy($conditions,$constructor=null)
	{
		$model = static::find()->filterBy($conditions);
		if( null === $constructor )
		{
			$constructor = array_keys($model->recordColumnsGet());
		}
		if( is_callable($constructor) )
		{
			$constructor($model);
		}
		else
		{
			arrayize($constructor);
			$model->setColumns($constructor);
		}
		return $model->load();
	}

	/**
	 * @static
	 * @param array $conditions
	 * @param array|callable|null $constructor
	 *
	 * @return Lib_Model_Set
	 */
	public static function getSetBy($conditions,$constructor=null)
	{
		$model = static::find()->filterBy($conditions);
		if( null === $constructor )
		{
			$constructor = array_keys($model->recordColumnsGet());
		}
		if( is_callable($constructor) )
		{
			$constructor($model);
		}
		else
		{
			arrayize($constructor);
			$model->setColumns($constructor);
		}
		return $model->loadSet();
	}

	/**
	 * @static
	 *
	 * @param $name
	 * @param $args
	 *
	 *
	 * @return array[self]|self|mixed
	 * @throws Lib_Exception
	 * @throws Exception
	 */
	public static function __callStatic($name, $args)
	{
		$matches = [];
		if( preg_match('/^get(List|Set)*By([a-zA-Z]+)$/', $name, $matches) )
		{
			/** @var Lib_Model $model */
			$model = new static;
			$cond = [];
			$list = false;
			$set = false;
			if( !empty($matches[1]) )
			{
				if($matches[1] == 'List')
				{
					$list = true;
				}
				else
				{
					$set = true;
				}
			}

			$filter = new Zend_Filter_Word_CamelCaseToSeparator('_');
			$attr = strtolower($filter->filter($matches[2]));

			if( $attr == 'id' )
			{
				$attr = $model->getPrimaryKey();
			}

			if( !array_key_exists($attr, $model->toArray()) )
			{
				$class = get_class($model);
				throw new Exception("Attribute '{$attr}' not exists in model '{$class}'");
			}

			$cond[$attr] = array_shift($args);

			if($set)
			{
				return static::getSetBy($cond);
			}

			$result = static::getListBy($cond);

			if( !$list )
			{
				if( count($result) > 1 )
				{
					$class = get_class($model);
					throw new Exception("Trying get more than 1 row by attribute '{$attr}' from model '{$class}'");
				}
				elseif( count($result) == 1 )
				{
					return array_shift($result);
				}
				else
				{
					return new static;
				}
			}
			else
			{
				return $result;
			}
		}
		else
		{
			throw new Exception("Static method '{$name}' not implemented.");
		}
	}

	/**
	 * @static
	 * @param int $id
	 *
	 * @return string
	 */
	public static function getCountById($id)
	{
		return static::find()->countById($id);
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	public function countById($id)
	{
		return $this->countBy([$this->getPrimaryKey()=>$id]);
	}

	/**
	 * @param array $cond
	 * @return int
	 */
	public function countBy($cond)
	{
		return $this->filterBy($cond)->count();
	}

	/**
	 * @param Zend_Db_Select|null $q
	 *
	 * @return $this
	 */
	public function loadOne($q = null)
	{
		$collection = $this->load($q,true);

		debug_enforce_count_gte( $collection, 1 );
		debug_assert_count_eq( $collection, 1 );
		return current($collection);
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @return $this
	 */
	public function loadOneNullable( $q = null )
	{
		$collection = $this->load( $q, true );

		if( empty($collection) )
		{
			$model = $this->modelFactory( [] );
		}
		else
		{
			debug_assert_count_eq( $collection, 1 );
			$model = reset($collection);
		}
		return $model;
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @param bool                $collection
	 * @param int                 $mode
	 * @return array
	 */
	public function loadOneArray( $q=null, $collection=false, $mode=self::LOAD_ARRAY_MODE_NESTED_COLUMN )
	{
		$collection = $this->loadArray( $q, $collection, $mode );
		debug_enforce_count_gte( $collection, 1 );
		debug_assert_count_eq( $collection, 1 );
		return reset($collection);
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @param mixed|null $null
	 * @return array|null
	 */
	public function loadOneArrayNullable( $q=null, $null=null )
	{
		$collection = $this->loadArray( $q, true );
		if( empty($collection) )
		{
			$row = $null;
		}
		else
		{
			$row = reset($collection);
		}
		return $row;
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @return mixed
	 */
	public function loadColumn( $q = null )
	{
		$record = $this->loadOneArray( $q, true );
		$columns = array_flatten( $record );
		debug_enforce_count_gte( $columns, 1 );
		debug_assert_count_eq( $columns, 1 );
		return array_shift( $columns );
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @param mixed $null
	 * @return mixed
	 */
	public function loadColumnNullable( $q=null, $null=null )
	{
		$record = $this->loadOneArrayNullable( $q, $null );
		if( $record===$null )
		{
			$ret = $null;
		}
		else
		{
			$columns = array_flatten( $record );
			if( empty($columns) )
			{
				$ret = $null;
			}
			else
			{
				debug_assert_count_eq( $columns, 1 );
				$ret = array_shift($columns);
			}
		}
		return $ret;
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @return int
	 */
	public function loadInt( $q=null )
	{
		return intval( $this->loadColumn( $q ) );
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @return float
	 */
	public function loadFloat( $q=null )
	{
		return floatval( $this->loadColumn( $q ) );
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @return string
	 */
	public function loadString( $q=null )
	{
		return strval( $this->loadColumn( $q ) );
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @param mixed $null
	 * @return int|null
	 */
	public function loadIntNullable( $q=null, $null=null )
	{
		$ret = $this->loadColumnNullable( $q, $null );
		return $ret===$null ? $ret : intval($ret);
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @param mixed $null
	 * @return float|null
	 */
	public function loadFloatNullable( $q=null, $null=null )
	{
		$ret = $this->loadColumnNullable( $q, $null );
		return $ret===$null ? $ret : floatval($ret);
	}

	/**
	 * @param Zend_Db_Select|null $q
	 * @param mixed $null
	 * @return string|null
	 */
	public function loadStringNullable( $q=null, $null=null )
	{
		$ret = $this->loadColumnNullable( $q, $null );
		return $ret===$null ? $ret : strval($ret);
	}

	protected function preLoad()
	{
	}

	protected function postLoad()
	{
	}

	/**
	 * @return $this
	 */
	public function debug()
	{
		$value = $this->recordExists() ? $this->toArray() : $this->getSQL();
		debug($value);
		return $this;
	}

	/**
	 * Returns result keys (aliases).
	 * @return array
	 */
	public function getColumnAliases( )
	{
		return array_map_val( $this->getColumns(), zend_column_name_dg() );
	}

	/**
	 * @param int $target
	 * @return $this
	 */
	public function readId( &$target )
	{
		return $this->read( $this->getPrimaryKey(), $target );
	}

	/**
	 * @param int $value
	 * @return $this
	 */
	public function storeId( $value )
	{
		return $this->store( $this->getPrimaryKey(), $value );
	}

	/**
	 * @deprecated
	 * @param Zend_Db_Select|null $q
	 * @return static
	 */
	public function loadNullable( $q=null )
	{
		//debug_assert( false, 'loadNullable is deprecated in favor of loadOneNullable' );
		return $this->loadOneNullable( $q );
	}

	/**
	 * @deprecated
	 * @param callable $iterator
	 */
	public function each($iterator)
	{
		debug_assert( false, 'direct each() call is deprecated in favor use of loadSet()->each()');
		return $this->loadSet()->each( $iterator );
	}
	
}
