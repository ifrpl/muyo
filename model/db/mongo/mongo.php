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
 * @property Lib_Db_Mongo_Select _select
 */
abstract class Lib_Model_Db_Mongo extends Lib_Model_Db
{
	protected $_primaryKey = '_id';

	private $_buildCondutions;

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

		$this->changedColumnsReset();

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
		Space_Model_Log::changeLog(Space_Model_Log::TYPE_DELETE, $this);

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

	/**
	 * @var bool $clearPK
	 * @return $this
	 */
	public function clearColumns($clearPK = false)
	{
		$this->getSelect()->clearColumns();
		if( !$clearPK )
		{
			$this->setColumns(array(
				'id' => $this->getPrimaryKey()
			));
		}
		return $this;
	}

	/**
	 * @todo $correlationName
	 * @param string $cols
	 * @param null   $correlationName
	 *
	 * @return $this
	 */
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
		$this->getSelect()->setColumns($cols, $correlationName);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getColumns()
	{
		return $this->getSelect()->getColumns();
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
			if(isset($item['condition']) && isset($item['value']))
			{
				$condition = $item['condition'];
				switch($condition)
				{
					case "IN":
						$condition = '$in';
						break;
					default:
						throw new Exception('Mongo condition "'.$condition.'" not implemented');
				}

				$this->_buildCondutions[$key] = array(
					$condition => $item['value']
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
	 * @todo: please do not use $q for now
	 * @param null $q
	 * @param bool $collection do not index by id
	 *
	 * @return array
	 */
	public function load($q = null, $collection = false)
	{
		$pkey = $this->getPrimaryKey();
		if( !__($this->getColumns())->any(function($arr)use($pkey){ return $arr[2]===$pkey; }) )
		{
			$this->setColumns($pkey);
		}

		$data = array();
		$select = $this->getSelect();

		$groups = $select->getGroups();
		$initial = array("items" => array());
		$reduce = "function (obj, prev) { prev.items.push(obj); }";
//		if(!empty($groups))
//		{
//			$cursor = $this->getCollection()->group($groups);
//		}
//		else
//		{
			$cursor = $this->getCollection()->find($select->getConditions(), $select->getColumns());
//		}

		if($select->getOrder())
		{
			$cursor->sort($select->getOrder());
		}
		if($select->getLimit())
		{
			$cursor->limit($select->getLimit());
		}
		$cursor->skip($select->getSkip());

//		if(get_class($this) == 'Workflow_Model_Step') debug($cursor->info());


		foreach( $cursor as $row )
		{
			/** @var Lib_Model_Db_Mongo $obj */
			$obj = $this->modelFactory($row);
			$obj->changedColumnsReset();

			if( !$collection && $obj->id )
			{
				$id = (string) $obj->id;
				$data[$id] = $obj;
			}
			else
			{
				$data[] = $obj;
			}
		}
		return $data;
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
	 * @return null|string
	 */
	public function getSQL()
	{
		$select = $this->getSelect();

		$cursor = $this->getCollection()->find($select->getConditions(), $select->getColumns());

		if($select->getOrder())
		{
			$cursor->sort($select->getOrder());
		}
		if($select->getLimit())
		{
			$cursor->limit($select->getLimit());
		}
		$cursor->skip($select->getSkip());

		return $cursor->info();
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
	 * @fixme we might consider deprecating this one after providing alternatives
	 * @param string $name
	 *
	 * @return $this
	 */
	public function clearPart($name)
	{
		debug_assert(false);
		return $this;
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
		if (!$this->_select)
		{
			$this->_select = new Lib_Db_Mongo_Select();
		}
		return $this->_select;
	}

	/**
	 * @param Lib_Db_Mongo_Select $select
	 *
	 * @return $this
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


}