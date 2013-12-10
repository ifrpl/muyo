<?php

if( !class_exists( 'Lib_Model' ) )
{
	require_once( implode( DIRECTORY_SEPARATOR, array(__DIR__, '..','model.php') ) );
}

/**
 * @package App
 *
 * @method static Lib_Model_Db getByColumn($value) returns loaded row, that has "column" matching $value
 * @method static array getListByColumn($value) returns all rows, that has "column" matching $value
 * @method static array getListById(int $id)
 */
abstract class Lib_Model_Db extends Lib_Model
{

	/**
	 * Table name
	 * @var string
	 */
	protected $_table;

	/**
	 * Name of primary key
	 * @var string|array
	 */
	protected $_primaryKey = 'id';

	/**
	 * @var Zend_Db_Select
	 */
	protected $_select;

	/**
	 * @var string
	 */
	protected $_alias;

	abstract public function getDb();

	/**
	 * @param array|int|null $options
	 */
	public function __construct($options = null)
	{
		$this->_alias = strtolower(str_replace('_', '', get_class($this)));

		parent::__construct($options);

		$this->normalizeColumns();
	}

	/**
	 * Return row object for current id
	 *
	 * @return Zend_Db_Table_Row|null
	 */
	public function getRow()
	{
		if( null !== ($id = $this->{$this->_primaryKey}) )
		{
			$row = $this->findOneBy(array($this->_primaryKey => $this->{$this->_primaryKey}));
			return $row;
		}
		return null;
	}

	abstract public function save();
	abstract public function delete();

	/**
	 *  Delete records by specified conditions.
	 *
	 * I'm moving it back as a stub because of our need of logging deleted objects
	 *
	 * @param array $condition array of conditions
	 * @return bool operation status
	 */
	public function deleteBy($condition = null)
	{
		if( !is_null($condition) )
		{
			$this->filterBy($condition);
		}
		$matched = $this->load();
		__($matched)->each(function($model)
		{ /** @var Lib_Model_Db $model */
			$model->delete();
		});
		return count($matched) > 0;
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
			$this->_select->from(array($this->getAlias() => $this->getTable()), $cols);
		}
		return $this->_select;
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

	/**
	 * @return $this
	 */
	public function clearColumns()
	{
		$this->clearPart('columns');
		return $this;
	}

	/**
	 * @param array|string $cols comma-separated columns list, *, or array of columns to read during query
	 * @param null $correlationName
	 * @return Lib_Model_Db
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
	 * Sets "name" column from this table for SELECTing, and being visible on grid/forms
	 * @param string $name
	 * @param string|null $alias
	 * @return $this
	 */
	protected function columnSet($name,&$alias=null)
	{
		if( is_null($alias) )
		{
			$alias = $name;
			$this->setColumns($name);
		}
		else
		{
			$this->setColumns(array($alias=>$name));
		}
		$this->addSetting($alias,self::settingDefaultGet($name));
		return $this;
	}

	/**
	 * @return array
	 */
	public function getColumns()
	{
		return $this->getSelect()->getPart('columns');
	}

	/**
	 * Replace dreaded '*' with all columns
	 */
	public function normalizeColumns()
	{
		$model_alias = $this->getAlias();
		$ret = array();

		foreach( $this->getColumns() as $descriptor )
		{
			$alias = $descriptor[0];
			if( $alias === $model_alias )
			{
				if( null === $descriptor[2] && $descriptor[1] === '*' )
				{
					foreach( array_keys($this->schemaColumnsGet()) as $column_name )
					{
						$ret []= array($alias, $column_name, null);
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

		$this->clearColumns();

		foreach( $ret as $descriptor )
		{
			if ( $descriptor[2] )
				$this->setColumns(array($descriptor[2] => $descriptor[1]), $descriptor[0]);
			else
				$this->setColumns($descriptor[1], $descriptor[0]);
		}
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	protected function isColumnSetLocally($name)
	{
		$columns = $this->getColumns();
		return __($columns)->contains(function($descriptor) use ($name)
		{
			$alias = $descriptor[2];
			$column = $descriptor[1];
			return $name === (null === $alias ? $column : $alias);
		});
	}

	/**
	 * @param string $cond
	 * @param mixed $value
	 * @param null|int $type
	 * @return Lib_Model_Db
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
	protected function prepareTableForJoin($name)
	{
		$alias = null;

		if( is_array($name) )
		{
			$alias = key($name);
			$name  = current($name);
		}

		if( is_string($name) && class_exists($name) )
		{
			$name = new $name;
		}

		if( $name instanceOf Lib_Model )
		{
			if(is_null($alias))
			{
				$alias = $name->getAlias();
			}

			$name = array($alias => $name->getTable());
		}
		elseif( $alias )
		{
			$name = array($alias => $name);
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
	 * @return string
	 */
	public function getAlias()
	{
		return $this->_alias;
	}

	/**
	 * @param string $value
	 */
	public function setAlias($value)
	{
		$this->_alias = $value;
	}

	/**
	 * @throws Exception
	 * @return string
	 */
	public function getTable()
	{
		if( is_null($this->_table) )
		{
			throw new Exception('Table name is empty');
		}
		return $this->_table;
	}

	/**
	 * @param string|array $cond condition (if one param) or column name if more than one params
	 *
	 * @return $this
	 */
	public function filterBy($cond)
	{
		$select = $this->getSelect();

		if ( is_array($cond) )
		{
			foreach($cond as $col => $value)
			{
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
	 *
	 * @return $this
	 */
	public function filterById($id)
	{
		$alias = $this->getAlias();
		$key = $this->getPrimaryKey();
		return $this->filterBy(array("{$alias}.{$key}"=>$id));
	}

	/**
	 * @param int|array|Zend_Db_Expr $id
	 * @return $this
	 */
	public function filterNotById($id)
	{
		$alias = $this->getAlias();
		$key = $this->getPrimaryKey();
		return $this->filterNotBy(array("{$alias}.{$key}"=>$id));
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
	 * @static
	 * @param bool $clearColumns
	 * @deprecated please use find instead
	 * @see find
	 *
	 * @return static
	 */
	public static function get($clearColumns = false)
	{
		/** @var Lib_Model $ret */
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
	 * @deprecated please use getListBy instead
	 * @param array $cond
	 * @return array
	 */
	public function findBy($cond)
	{
		$select = $this->getSelect();
		$select->reset('where');
		foreach($cond as $col => $value)
		{
			if( is_array($value) )
			{
				$valueChar = '?';
				$cond = '=';

				if(isset($value['condition']) && isset($value['value']))
				{
					$cond = $value['condition'];
					$value = $value['value'];
					$valueChar = '?';
				}

				if( is_array($value) )
				{
					$cond = 'IN';
					$valueChar = '(?)';
				}

				$select->where($col.' '. $cond .' '.$valueChar, $value);
			}
			else
			{
				if( is_null($value) )
				{
					$select->where($col.' IS NULL');
				}
				else
				{
					$select->where($col.' = ?', $value);
				}
			}
		}

		return $this->load($select);
	}

	/**
	 * @param int $id
	 *
	 * @return static
	 */
	public static function findById($id)
	{
		return self::find()->filterById($id);
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
		$this->setLimit(1);
		$results = $this->findBy($conditions);

		if( count($results) == 0 )
		{
			$this->fromArray($conditions);
		}
		else
		{
			$c = current($results);
			$this->fromArray($c->toArray());
		}
		$this->changedColumnsReset();
		return $this;
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
	 * @return static
	 */
	public static function getById($id)
	{
		$dummy = self::find();
		return self::getBy(array($dummy->getPrimaryKey()=>$id));
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
		$model = self::find()->filterBy($conditions);
		if( null === $constructor )
		{
			$constructor = array_keys($model->_data);
		}
		if( is_array($constructor) )
		{
			$model->setColumns($constructor);
		}
		else
		{
			$constructor($model);
		}
		return $model->load();
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
		$matches = array();
		if( preg_match('/^get(List)*By([a-zA-Z]+)$/', $name, $matches) )
		{
			/** @var Lib_Model $model */
			$model = new static;
			$cond = array();
			$list = false;
			if( !empty($matches[1]) )
			{
				$list = true;
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
			$result = self::getListBy($cond);

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
			throw new Lib_Exception("Static method '{$name}' not implemented.");
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
		return self::find()->countById($id);
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	public function countById($id)
	{
		return $this->countBy(array($this->getPrimaryKey()=>$id));
	}

	/**
	 * @param array $cond
	 * @return string
	 */
	public function countBy($cond)
	{
		$this->filterBy($cond)->count();
	}

	/**
	 * @return int
	 */
	public function count()
	{
		$ret = $this->getDb()->fetchOne($this->getSelect()->reset('columns')->columns('count(1)'));
		return intval($ret);
	}

	abstract public function load($q = null, $collection = false);

	/**
	 * @param null $q
	 * @param bool $collection
	 *
	 * @return Lib_Model_Db
	 */
	public function loadOne($q = null, $collection = false)
	{
		$ret = $this->load($q,$collection);
		$count = count($ret);
		debug_enforce( 1 === $count, "loadOne expects single result, but $count given" );

		return array_shift($ret);
	}

	/**
	 * @param callable $iterator
	 */
	public function each($iterator)
	{
		__($this->load())->each($iterator);
	}

	/**
	 * WARNING: do not use if don't know internals (partially implemented)
	 * Load model from SQL query to an array with joined columns as arrays
	 * @return array
	 */
	public function loadArray()
	{
		$alias = $this->getAlias();
		$key_name = $this->getPrimaryKey();
		$key_idx = null;

		if( count($this->getColumns()) == 0 )
		{
			$this->setColumns(array_keys($this->schemaColumnsGet()), $alias);
		}
		$descriptors = $this->getColumns();
		foreach( $descriptors as $idx=>$descriptor )
		{
			$colname = null !== $descriptor[2] ? $descriptor[2] : $descriptor[1];
			$tblalias = $descriptor[0];
			if( $tblalias === $alias && $colname === $key_name )
			{
				$key_idx = $idx;
			}
		}

		debug_assert(null !== $key_idx,array($alias,$key_name));

		$db = $this->getDb();
		try {
			$rows = $db->fetchAll($this->getSQL(), array(),Zend_Db::FETCH_NUM);
		}
		catch( Exception $e )
		{
			throw new Exception('Error while loading: '.$e->getMessage().' | SQL: '.$this->getSQL());
		}

		$ret = array();
		foreach ( $rows as $row )
		{
			$key = $row[$key_idx];
			if( !array_key_exists($key, $ret) )
			{
				$ret[$key] = array();
			}

			foreach( $row as $idx=>$column )
			{
				$descriptor = $descriptors[$idx];
				$colalias = 3===count($descriptor)&&$descriptor[2] ? $descriptor[2] : $descriptor[1];
				$tblalias = $descriptor[0];

				if( !array_key_exists($tblalias, $ret[$key]) )
				{
					$ret[$key][$tblalias] = array();
				}

				$is_joined = $tblalias !== $alias;
				if( $is_joined )
				{
					if( array_key_exists($colalias, $ret[$key][$tblalias]) )
					{
						$ret[$key][$tblalias][$colalias] []= $column;
					}
					else
					{
						$ret[$key][$tblalias][$colalias] = array($column);
					}
				}
				else
				{
					$ret[$key][$tblalias][$colalias] = $column;
				}
			}
		}
		return $ret;
	}

	protected function preLoad()
	{
	}

	protected function postLoad()
	{
	}

	protected function clearAfterLoad()
	{
		$this->_select = null;
	}

	/**
	 * @return null|string
	 */
	public function getSQL()
	{
		return $this->getSelect()->assemble();
	}

	/**
	 * @param string $export Name of deploy
	 * @param null|mixed $source
	 * @return Bvb_Grid
	 */
	public function getDataTable($export = 'JqGrid', $source = null)
	{
		set_time_limit(0);

		if(is_null($source))
		{
			$source = new Lib_Grid_Source_Model($this);
		}

		$config = $this->getGridConfig();

		$id = Lib_Grid::buildId(
			  $export,
			  $source,
			  isset($config->bvbParams->id_prefix) ? $config->bvbParams->id_prefix : null
		);

		$requestParams = Zend_Controller_Front::getInstance()->getRequest()->getParams();
		if(isset($requestParams['q']) && $requestParams['q'] == $id && isset($requestParams['_exportTo']))
		{
			$requestParams['_exportTo'.$id] = $requestParams['_exportTo'];
		}

		/**
		 * @var Bvb_Grid $grid
		 */
		$grid = Bvb_Grid::factory($export, $config, $id, array(), $requestParams);
		if($export == 'JqGrid')
		{
			Lib_Grid::prepareDeploy($grid, $config, $source);
		}
		else {
			if($export == 'Pdf')
			{
				$config->export->pdf   = 'Pdf';
				$config->disableExport = false;
				Lib_Grid::prepareDeploy($grid, $config, $source);
			}
			else
			{

			}
		}

		return $grid;
	}

	/**
	 * TODO: unify it with Lib_Grid
	 * @deprecated
	 * @return Zend_Config
	 */
	public function getGridConfig()
	{
		$config = new Zend_Config(array(), true);
		if(Zend_Registry::isRegistered('config') && isset(Zend_Registry::get('config')->dataGrid))
		{
			$config->merge(Zend_Registry::get('config')->dataGrid);
		}
		$config->merge(new Zend_Config($this->_settings));

		foreach($config as $optionName => $optionValue)
		{
			if(!isset($optionValue->title) && isset($optionValue->label))
			{
				$config->$optionName->title = $optionValue->label;
				unset($config->$optionName->label);
			}
			if(isset($optionValue->visible) && !$optionValue->visible)
			{
				$config->$optionName->hidden = true;
			}

			if(isset($optionValue->type))
			{
				if(!isset($config->$optionName->jqg))
				{
					$config->$optionName->jqg = new Zend_Config(array(), true);
				}
				switch($optionValue->type)
				{
					case "select":
						$multiOptions = array();
						$multiOptions[''] = 'LABEL_ALL';
						$multiOptions += $config->$optionName->multiOptions->toArray();

						$multiOptions = array_map(function($key, $row){
							$translate = App_Translate::getInstance();
							return $key.':'.$translate->translate($row);
						}, array_keys($multiOptions), $multiOptions);
						ksort($multiOptions);

						$multiOptions = implode(';', $multiOptions);

						$config->$optionName->jqg->merge(new Zend_Config(array(
							'stype' => 'select',
							'searchoptions' => array(
								'sopt' => array(
									'eq'
								),
								'value' => $multiOptions
							),
							'searchType' => '='
						)), true);
						break;
					case "date":
						$config->$optionName->merge(new Zend_Config(array(
							'sorttype' => 'date',
							'format'   => array(
								'date',
								array(
									'date_format' => Zend_Date::DATE_MEDIUM
								)
							),
							'jqg'      => array(
								'searchoptions' => array(
									'dataInit' => new Zend_Json_Expr('function(el){
											jQuery(el).datepicker({
													dateFormat: "yy-mm-dd",
													onSelect: function(dateText, inst){
															jQuery(el).parents(".ui-jqgrid").find(".ui-jqgrid-btable").get(0).triggerToolbar();
													}
											});
									}')
								)
							)
						)), true);

						if(!isset($config->$optionName->defaultvalue))
						{
							$config->$optionName->defaultvalue = null;
						}
						break;
					case "datetime":
						$config->$optionName->merge(new Zend_Config(array(
							'sorttype' => 'date',
							'format'   => array(
								'date',
								array(
									'date_format' => Zend_Date::DATETIME_SHORT
								)
							),
							'jqg'      => array(
								'searchoptions' => array(
									'dataInit' => new Zend_Json_Expr('function(el){
											jQuery(el).datetimepicker({
													dateFormat: "yy-mm-dd",
													timeFormat: "hh:mm",
													onSelect: function(dateText, inst){
															jQuery(el).parents(".ui-jqgrid").find(".ui-jqgrid-btable").get(0).triggerToolbar();
													}
											});
									}')
								)
							)
						)), true);

						if(!isset($config->$optionName->defaultvalue))
						{
							$config->$optionName->defaultvalue = null;
						}
						break;
					case "time":
						$config->$optionName->merge(new Zend_Config(array(
							'sorttype' => 'date',
							'format'   => array(
								'date',
								array(
									'date_format' => Zend_Date::TIME_SHORT
								)
							),
							'jqg'      => array(
								'searchoptions' => array(
									'dataInit' => new Zend_Json_Expr('function(el){
											jQuery(el).timepicker({
													timeFormat: "hh:mm",
													onSelect: function(dateText, inst){
															jQuery(el).parents(".ui-jqgrid").find(".ui-jqgrid-btable").get(0).triggerToolbar();
													}
											});
									}')
								)
							)
						)), true);

						if(!isset($config->$optionName->defaultvalue))
						{
							$config->$optionName->defaultvalue = null;
						}
						break;
					case "boolean":
					case "bool":
						$multiOptions = array(
							'' => 'LABEL_ALL',
							'0' => 'LABEL_NO',
							'1' => 'LABEL_YES'
						);
						$multiOptions = array_map(function($key, $row){
							$translate = App_Translate::getInstance();
							return $key.':'.$translate->translate($row);
						}, array_keys($multiOptions), $multiOptions);

						$multiOptions = implode(';', $multiOptions);

						$config->$optionName->merge(new Zend_Config(array(
							'width' => 30,
							'align' => 'center',
							'jqg' => array(
								'stype' => 'select',
								'searchoptions' => array(
									'sopt' => array(
										'eq'
									),
									'value' => $multiOptions
								)
							),
							'searchType' => '='
						)), true);

						if(!isset($config->$optionName->helper) && !isset($config->$optionName->callback))
						{
							$config->$optionName->merge(new Zend_Config(array(
								'jqg' => array(
									'formatter' => 'checkbox'
								)
							)), true);
						}

						break;
					case "int":
						$config->$optionName->merge(new Zend_Config(array(
							'searchType' => '='
						)), true);

						break;
					default:
						debug_assert(false !== array_search($optionValue->type, self::$types), "Unknown Grid Cell Type `{$optionValue->type}`");
				}
			}

		}

		return $config;
	}

	/**
	 * @param Zend_Db_Select $select
	 * @return $this
	 */
	public function setSelect($select)
	{
		$this->_select = $select;
		return $this;
	}

	/**
	 * @param Lib_Model_db $model
	 * @param string|null $column
	 * @return string
	 */
	private static function prefixColumn($model, $column = null)
	{
		if( is_null($column) )
		{
			$column = $model->getAlias().'.'.$model->getPrimaryKey();
		}
		elseif( false === strpos($column,'.') )
		{
			$column = $model->getAlias().'.'.$column;
		}
		return $column;
	}


	/**
	 * WARNING: Only columns copying supported.
	 * @param Lib_Model $model
	 * @param string|null $thisKeyCol may contain table prefix or not
	 * @param string|null $thatKeyCol may contain table prefix or not
	 * @param string $conditions ['and' => '{this}.column={that}.column' ]
	 * @return Lib_Model_db
	 */
	public function joinTo($model,$thisKeyCol,$thatKeyCol=null,$conditions='')
	{
		debug_assert( null !== $thatKeyCol || null !== $thisKeyCol );

		$thatKeyCol = self::prefixColumn($model, $thatKeyCol);
		$thisKeyCol = self::prefixColumn($this, $thisKeyCol);

		$conditions = str_replace('{that}',$model->getAlias(),$conditions);
		$conditions = str_replace('{this}',$this->getAlias(),$conditions);

		$this->setJoinLeft($model, "{$thisKeyCol}={$thatKeyCol} ".$conditions, '');
		$model->settingsJoin($this);
		foreach( $model->getColumns() as $descriptor )
		{
			$table = $descriptor[0];
			$column = $descriptor[1];
			$alias = 3===count($descriptor) ? $descriptor[2] : null;

			if( null !== $alias )
			{
				$this->setColumns(array($alias=>$column),$table);
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
	 * @param Lib_Model_db $model
	 * @param string|null $thisKeyCol may contain table prefix or not
	 * @param string|null $thatKeyCol may contain table prefix or not
	 * @param string $conditions
	 * @return $this
	 */
	public function joinFrom($model,$thisKeyCol,$thatKeyCol=null,$conditions='')
	{
		debug_assert( null !== $thatKeyCol || null !== $thisKeyCol );

		$thatKeyCol = self::prefixColumn($model, $thatKeyCol);
		$thisKeyCol = self::prefixColumn($this, $thisKeyCol);

		$conditions = str_replace('{that}',$model->getAlias(),$conditions);
		$conditions = str_replace('{this}',$this->getAlias(),$conditions);

		$this->setJoinLeft($model, "{$thisKeyCol}={$thatKeyCol} ".$conditions, '');
		foreach( $model->getColumns() as $descriptor )
		{
			$table = $descriptor[0];
			$column = $descriptor[1];
			$alias = 3===count($descriptor) ? $descriptor[2] : null;
			if( null !== $alias )
			{
				$this->setColumns(array($alias=>$column),$table);
			}
			else
			{
				$this->setColumns($column,$table);
			}
		};
		$this->settingsJoin($model);
//		if( $this instanceof Model_Event_Invoice_Accommodation && $model instanceof Model_Reference)
//		{
//			$map = function($desc){ return (count($desc)===3&&$desc[2]) ? $desc[2]: $desc[1]; };
//			debug(array_map_val(array(
//				$this->_settings,$this->_settingsJoined, array_map_key($this->getColumns(), $map),
//				$model->_settings,$model->_settingsJoined, array_map_key($model->getColumns(), $map)
//			),function($val){ return array_keys($val); }));
//		}
//		$select = $model->getSelect();
		return $this;
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

}