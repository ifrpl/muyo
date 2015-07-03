<?php

if( !class_exists('Lib_Model_Db') )
{
	require_once( implode( DIRECTORY_SEPARATOR, array(__DIR__,'..','db.php') ) );
}

if( !class_exists('Lib_Db_Mongo_Select') )
{
	require_once( implode( DIRECTORY_SEPARATOR, array(__DIR__,'select.php') ) );
}

/**
 * @package App
 * @subpackage Db
 *
 * @method MongoDB getDb()
 *
 * @property MongoID id
 */
abstract class Lib_Model_Db_Mongo extends Lib_Model_Db
{
	protected $_primaryKey = '_id';

	/** @var Lib_Db_Mongo_Select */
	private $_select;
	private $_buildCondutions;

	private $_timeout = null;

	protected $_settings = array(
		'_id' => array(
			'hidden' => true
		)
	);

	/**
	 * @param string $name
	 * @param array $setting
	 * @param mixed $defaultValue
	 */
	protected function schemaColumnApplyDefault(&$name, &$setting, &$defaultValue)
	{
		if( $name == $this->getPrimaryKey() )
		{
			array_set_default($setting,'type','string');
		}
		parent::schemaColumnApplyDefault($name,$setting,$defaultValue);
	}

	/**
	 * @return $this
	 */
	public function save()
	{
		$data = $this->serializeContent();

		$collection = $this->getCollection();

		if(isset($data[$this->_primaryKey]) && !empty($data[$this->_primaryKey]))
		{
			$id = $this->{$this->_primaryKey};
			if(!($id instanceof MongoId))
			{
				$id = new MongoId($id);
			}
			$where = array($this->_primaryKey => $id);

			unset($data[$this->_primaryKey]);
			$collection->update($where, $data);

			$this->_onUpdate();
		}
		else
		{
			unset($data[$this->_primaryKey]);

			$collection->insert($data);
			$this->{$this->_primaryKey} = $data[$this->_primaryKey];

			$this->_onInsert();
		}

		$this->_onSave();
		$this->changedColumnsReset();

		return $this;
	}

	/**
	 * @param int $timeout Mongo Cursor timeout in milliseconds. Use -1 to wait forever
	 *
	 * @return $this
	 */
	public function setTimeout($timeout)
	{
		$this->_timeout = $timeout;
		return $this;
	}

	public function serialize()
	{
		return $this->getRef();
	}

	/**
	 * @return array
	 */
	public function serializeContent()
	{
		$data = parent::serializeContent();

		foreach($data as $key => $value)
		{
			$value = $this->recordColumnGet($key);

			if($value instanceof Lib_Model)
			{
				$data[$key] = $value->serialize();
			}
			if(is_array($value))
			{
				$data[$key] = $value;
			}
		}

		return $data;
	}

	/**
	 * @throws Exception
	 * @return bool
	 */
	public function delete()
	{
		$pkey = $this->getPrimaryKey();
		$pkval = $this->{$pkey};
		if(is_null($pkval))
		{
			throw new Exception('Nothing to delete, id is empty');
		}

		if(!is_object($pkval)){
			$pkval = new MongoId($pkval);
		};

		$ret = $this->getCollection()->remove(array($pkey => $pkval), array('justOne' => true));

		$this->{$pkey} = null;

		$this->_onDelete();

		return 1 == $ret['ok'];
	}

	public function clearColumns($clearPK = false)
	{
		$this->getSelect()->clearFields();
		if( !$clearPK )
		{
			$this->setColumns(array(
				'id' => $this->getPrimaryKey()
			));
		}
		return $this;
	}

	public function setColumns($cols = '*', $correlationName = null)
	{
		if($cols === '*')
		{
			$cols = array_keys($this->schemaColumnsGet());
		}
		arrayize($cols);
		foreach($cols as $key => $val)
		{
			if('id'===$key)
			{
				unset($cols[$key]);
				$cols[$this->getPrimaryKey()] = $val;
			}
		}
		if(null === $correlationName)
		{
			$correlationName = $this->getAlias();
		}
		$this->getSelect()->setFields($cols, $correlationName);
		return $this;
	}

	/**
	 * @override
	 * @return array
	 */
	public function getColumns()
	{
		$alias = $this->getAlias();
		$fields = $this->getSelect()->getFields();
		$fieldsSet = array_filter_key( $fields, function()
		{
			$isSet = func_get_arg(0);
			return $isSet;
		});
		$ret = array_map_val( $fieldsSet, function()use($alias)
		{
			$name = func_get_arg(1);
			return array( $alias, $name, null );
		});
		return $ret;
	}

	/**
	 * @param array|string $cond
	 *
	 * @return $this
	 */
	public function filterBy($cond)
	{
		if(array_key_exists($this->_primaryKey, $cond) && !($cond[$this->_primaryKey] instanceof MongoId))
		{
			$cond[$this->_primaryKey] = new MongoId($cond[$this->_primaryKey]);
		}

		array_walk($cond, array(
			$this,
			'filterByWalk'
		));

		$cond = $this->_buildCondutions;

		$this->_buildCondutions = null;

		$this->getSelect()->setConditions($cond);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function filterFalse()
	{
		return $this->filterBy(array(
			self::getPrimaryKey() => array(
				'$exists' => 0
			),
		));
	}

	/**
	 * @param        $item
	 * @param        $key
	 * @param string $prefix
	 */
	private function filterByWalk($item, $key, $prefix = '')
	{
		if($item instanceof Lib_Model)
		{
			$item = $item->serialize();

		}

		if(is_array($item))
		{
			if(array_key_exists('condition', $item) && array_key_exists('value', $item))
			{
				$condition = $item['condition'];
				$value = $item['value'];

				switch($condition)
				{
					case "IN":
						$condition = '$in';
						$value = array_values($value);
						break;
					case "!=":
						$condition = '$ne';
						if(is_null($value))
						{
							$value = null;
						}
						break;
					case "REGEX":
						$condition = '$regex';
						$value = new MongoRegex("/{$value}/");
						break;
					default:
						throw new Exception('Mongo condition "'.$condition.'" not implemented');
				}

				$this->_buildCondutions[$key] = array(
					$condition => $value
				);
			}
			else
			{
				array_walk($item, array(
					$this,
					'filterByWalk'
				), $prefix . $key . '.');
			}
		}
		else
		{
			$this->_buildCondutions[$prefix . $key] = $item;
		}
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
	 * @return int
	 */
	public function count()
	{
		$select = $this->getSelect();
		return $this->getCollection()->count($select->getConditions(), $select->getLimit(), $select->getSkip());
	}

	/**
	 * @param null|Lib_Db_Mongo_Select $q
	 * @param bool $collection
	 * @return array
	 * @fixme support for multiple result collections
	 * @fixme $q needs relation with model
	 */
	public function loadArray( $q=null, $collection=false )
	{
		$pkey = $this->getPrimaryKey();
		if( !array_some( $this->getColumns(), function($arr)use($pkey){ return $arr[2]===$pkey; } ) )
		{
			$this->setColumns($pkey);
		}

		$data = array();
		$select = null === $q ? $this->getSelect() : $q;

		$groups = $select->getGroups();
		$initial = array("items" => array());
		$reduce = "function (obj, prev) { prev.items.push(obj); }";
//		if(!empty($groups))
//		{
//			$cursor = $this->getCollection()->group($groups);
//		}
//		else
//		{
			$cursor = $this->getCollection()->find($select->getConditions(), $select->getFields());
//		}

		if($this->_timeout)
		{
			$cursor->timeout($this->_timeout);
		}

		if($select->getOrder())
		{
			$cursor->sort($select->getOrder());
		}
		if($select->getLimit())
		{
			$cursor->limit($select->getLimit());
		}
		$cursor->skip($select->getSkip());

		foreach( $cursor as $row )
		{
			if( !$collection )
			{
				$id = (string) $row[$pkey];
				$data[$id] = $row;
			}
			else
			{
				$data[] = $row;
			}
		}
		$alias = $this->getAlias();
		return array( $alias => $data );
	}

	/**
	 * @todo: please do not use $q for now
	 * @param null $q
	 * @param bool $collection do not index by id
	 *
	 * @return array
	 */
	public function load( $q = null, $collection = false )
	{
		$alias = $this->getAlias();
		$array = $this->loadArray( $q, $collection );
		$t = $this;
		$ret = array_map_val( $array[$alias], function()use($t)
		{
			$row = func_get_arg(0);
			$obj = $t->modelFactory( $row );
			$obj->changedColumnsReset();
			return $obj;
		} );
		return $ret;
	}

	/**
	 * @return Lib_Model_Set
	 */
	public function loadSet()
	{
		$alias = $this->getAlias();
		$array = $this->loadArray( );
		$t = $this;

		$set = new Lib_Model_Set;
		$set->setResultSet($array[$alias]);
		$set->setModel($this);

		return $set;
	}

	/**
	 * @param array|string $order
	 *
	 * @return $this
	 */
	public function setOrderBy($order)
	{
		if(is_null($order))
		{
			$this->getSelect()->setOrder(null);
			return $this;
		}

		if(!is_array($order))
		{
			$order = array($order);
		}

		$sort = array();
		foreach($order as $value)
		{
			$direction = 1;
			if (preg_match('/(.*\W)(ASC|DESC)\b/si', $value, $matches)) {
				$value = trim($matches[1]);
				switch($matches[2])
				{
					case "ASC":
						$direction = 1;
						break;
					case "DESC":
						$direction = -1;
						break;
				}
			}

			$sort[$value] = $direction;
		}

		$this->getSelect()->setOrder($sort);
		return $this;
	}



	/**
	 * @return string
	 */
	public function getSQL()
	{
		$select = $this->getSelect();

		$cursor = $this->getCollection()->find($select->getConditions(), $select->getFields());

		if($select->getOrder())
		{
			$cursor->sort($select->getOrder());
		}
		if($select->getLimit())
		{
			$cursor->limit($select->getLimit());
		}
		$cursor->skip($select->getSkip());

		return print_r( $cursor->info(), true );
	}

	/**
	 * @param array|string $spec
	 *
	 * @return $this
	 */
	public function setGroup($spec)
	{
		debug_assert(false);
		$this->getSelect()->group($spec);
		return $this;
	}

	/**
	 * @param array|Lib_Model|string $name
	 * @param string                 $cond
	 * @param array|string           $cols
	 * @param null                   $schema
	 *
	 * @return $this
	 */
	public function setJoin($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * @param array|Lib_Model|string $name
	 * @param string                 $cond
	 * @param array|string           $cols
	 * @param null                   $schema
	 *
	 * @return $this
	 */
	public function setJoinLeft($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * @param array|Lib_Model|string $name
	 * @param string                 $cond
	 * @param array|string           $cols
	 * @param null                   $schema
	 *
	 * @return $this
	 */
	public function setJoinRight($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * @param array|Lib_Model|string $name
	 * @param string                 $cond
	 * @param array|string           $cols
	 * @param null                   $schema
	 *
	 * @return $this
	 */
	public function setJoinInner($name, $cond, $cols = Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * WARNING: Only columns copying supported.
	 * @param Lib_Model $model
	 * @param string|null $thisKeyCol may contain table prefix or not
	 * @param string|null $thatKeyCol may contain table prefix or not
	 * @param string $conditions ['and' => '{this}.column={that}.column' ]
	 * @return Lib_Model
	 */
	public function joinTo($model,$thisKeyCol,$thatKeyCol=null,$conditions='')
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * @fixme Asserting incompatibility with MongoDB
	 * @param string $export
	 * @param null   $source
	 *
	 * @return Bvb_Grid
	 */
	public function getDataTable($export = 'JqGrid', $source = null)
	{
		debug_assert(false);
		return null;
	}

	/**
	 * @fixme we might consider deprecating it
	 * @param string $cond
	 * @param null   $value
	 * @param null   $type
	 *
	 * @return $this
	 */
	public function setWhere($cond, $value = null, $type = null)
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * TODO: aggregation framework
	 * @param string $cond
	 * @param mixed $value
	 * @param null|int $type
	 * @return $this
	 */
	public function setHaving($cond, $value = null, $type = null)
	{
		debug_assert(false);
//		$this->getSelect()->having($cond, $value, $type);
		return $this;
	}

	/**
	 * FIXME: use cursors
	 * @param $page
	 * @param $rowCount
	 *
	 * @return $this
	 */
	public function setLimitPage($page, $rowCount)
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * TODO: needs newer version
	 * @param bool $flag
	 *
	 * @return $this
	 */
	public function setDistinct($flag = true)
	{
		$this->getSelect()->setDistinct($flag);
		return $this;
	}



	// Should be private

	/**
	 * Please do not use this one. It's private.
	 * @param bool   $clear
	 * @param string $cols
	 *
	 * @return Lib_Db_Mongo_Select
	 */
	public function getSelect($clear = false, $cols = '*')
	{
		if( !$this->_select )
		{
			$this->_select = new Lib_Db_Mongo_Select();
		}
		return $this->_select;
	}

	/**
	 * @param Lib_Db_Mongo_Select $select
	 * @return $this
	 * @override
	 */
	public function setSelect($select)
	{
		$this->_select = $select;
		return $this;
	}

	/**
	 * @return MongoCollection
	 */
	protected function getCollection()
	{
		/** @var $db MongoDB  */
		$db = $this->getDb();
		return new MongoCollection($db, $this->getTable());
	}

	/**
	 * @param string $name
	 * @param        $value
	 */
	public function __set($name, $value)
	{
		if( $name == 'id' )
		{
			$name = $this->_primaryKey;
		}

		$method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
		if ( method_exists($this, $method) )
		{
			$this->$method($value);
		}
		else
		{
			$this->recordColumnSet($name,$value);
		}
	}

	/**
	 * @return array
	 */
	public function getRef()
	{
		return MongoDBRef::create($this->_table, (is_object($this->id) ? $this->id : new MongoId($this->id)));
	}

	/**
	 * Return row object for current id
	 * @return $this
	 */
	public function getRow()
	{
		debug_assert( false, 'Function getRow is scheduled for deletion, replace with Model::getById( $id )' );

		$alias = $this->getAlias();
		$rows = static::findById( $this->id )->loadArray();
		$row = array_shift( $rows );
		$ret = $this->fromArray( $row[ $alias ], true );
		return $ret;
	}
}