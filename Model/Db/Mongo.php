<?php

namespace IFR\Main\Model\Db;

/**
 * @package App
 * @subpackage Db
 *
 * @method MongoDB getDb()
 *
 * @property MongoID id
 */
abstract class Mongo extends \IFR\Main\Model\Db
{
	const COL_ID = '_id';

	static protected $_primaryKey = self::COL_ID;

	/** @var \IFR\Main\Model\Db\Mongo\Select */
	private $_select;
	private $_buildConditions;

	private $_timeout = null;

	protected $_settings = array(
		self::COL_ID => array(
			self::SETTING_HIDDEN => true
		)
	);

	/**
	 * @param string $name
	 * @param array $setting
	 * @param mixed $defaultValue
	 */
	protected function schemaColumnApplyDefault(&$name, &$setting, &$defaultValue)
	{
		if( $name == self::getPrimaryKey() )
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

		$primaryKey = $this->getPrimaryKey();

		if(isset($data[$primaryKey]) && !empty($data[$primaryKey]))
		{
			$id = $this->{$primaryKey};
			if(!($id instanceof \MongoId))
			{
				$id = new \MongoId($id);
			}
			$where = array($primaryKey => $id);

			unset($data[$primaryKey]);
			$collection->update($where, $data);

			$this->_onUpdate();
		}
		else
		{
			unset($data[$primaryKey]);

			$collection->insert($data);
			$this->{$primaryKey} = $data[$primaryKey];

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

			if($value instanceof \IFR\Main\Model)
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
	 * @throws \Exception
	 * @return bool
	 */
	public function delete()
	{
		$pkey = self::getPrimaryKey();
		$pkval = $this->{$pkey};
		if(is_null($pkval))
		{
			throw new \Exception('Nothing to delete, id is empty');
		}

		if(!is_object($pkval)){
			$pkval = new \MongoId($pkval);
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
				'id' => self::getPrimaryKey()
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
				$cols[self::getPrimaryKey()] = $val;
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
		if(array_key_exists($this->_primaryKey, $cond) && !($cond[$this->_primaryKey] instanceof \MongoId))
		{
			$cond[$this->_primaryKey] = new \MongoId($cond[$this->_primaryKey]);
		}

		array_walk($cond, array(
			$this,
			'_filterByWalk'
		));

		$cond = $this->_buildConditions;

		$this->_buildConditions = null;

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
	private function _filterByWalk($item, $key, $prefix = '')
	{
		if($item instanceof \IFR\Main\Model)
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
						$value = new \MongoRegex("/{$value}/");
						break;
				}

				$this->_buildConditions[$key] = array(
					$condition => $value
				);
			}
			else
			{
				array_walk($item, array(
					$this,
					'_filterByWalk'
				), $prefix . $key . '.');
			}
		}
		else
		{
			$this->_buildConditions[$prefix . $key] = $item;
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
	 * @param null|\IFR\Main\Model\Db\Mongo\Select $q
	 * @param bool $collection
	 * @return array
	 * @fixme support for multiple result collections
	 * @fixme $q needs relation with model
	 */
	public function loadArray( $q=null, $collection=false, $mode=self::LOAD_ARRAY_MODE_NESTED_TABLE )
	{
		$pkey = self::getPrimaryKey();
		if( !array_some( $this->getColumns(), function($arr)use($pkey){ return $arr[2]===$pkey; } ) )
		{
			$this->setColumns($pkey);
		}

		$data = array();
		$select = null === $q ? $this->getSelect() : $q;

    	$cursor = $this->getCollection()->find($select->getConditions(), $select->getFields());

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
				$id = strval($row[$pkey]);
				$data[$id] = $row;
			}
			else
			{
				$data[] = $row;
			}
		}
		$alias = $this->getAlias();

		if(self::LOAD_ARRAY_MODE_NESTED_TABLE == $mode)
		{
			return array( $alias => $data );
		}

		return $data;
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
            if(null != $obj)
            {
                $obj->changedColumnsReset();
            }

			return $obj;
		} );
		return $ret;
	}

	/**
	 * @return \IFR\Main\Model\Set
	 */
	public function loadSet()
	{
		$alias = $this->getAlias();
		$array = $this->loadArray();

		$set = new \IFR\Main\Model\Set();
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
	 * @param array|\IFR\Main\Model|string $name
	 * @param string                 $cond
	 * @param array|string           $cols
	 * @param null                   $schema
	 *
	 * @return $this
	 */
	public function setJoin($name, $cond, $cols = \Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * @param array|\IFR\Main\Model|string $name
	 * @param string                 $cond
	 * @param array|string           $cols
	 * @param null                   $schema
	 *
	 * @return $this
	 */
	public function setJoinLeft($name, $cond, $cols = \Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * @param array|\IFR\Main\Model|string $name
	 * @param string                 $cond
	 * @param array|string           $cols
	 * @param null                   $schema
	 *
	 * @return $this
	 */
	public function setJoinRight($name, $cond, $cols = \Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * @param array|\IFR\Main\Model|string $name
	 * @param string                 $cond
	 * @param array|string           $cols
	 * @param null                   $schema
	 *
	 * @return $this
	 */
	public function setJoinInner($name, $cond, $cols = \Zend_Db_Select::SQL_WILDCARD, $schema = null)
	{
		debug_assert(false);
		return $this;
	}

	/**
	 * WARNING: Only columns copying supported.
	 * @param \IFR\Main\Model $model
	 * @param string|null $thisKeyCol may contain table prefix or not
	 * @param string|null $thatKeyCol may contain table prefix or not
	 * @param string $conditions ['and' => '{this}.column={that}.column' ]
	 * @return \IFR\Main\Model
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
	 * @return \Bvb_Grid
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
	 * @return \IFR\Main\Model\Db\Mongo\Select
	 */
	public function getSelect($clear = false, $cols = '*')
	{
		if( !$this->_select )
		{
			$this->_select = new \IFR\Main\Model\Db\Mongo\Select();
		}
		return $this->_select;
	}

	/**
	 * @param \IFR\Main\Model\Db\Mongo\Select $select
	 * @return $this
	 * @override
	 */
	public function setSelect($select)
	{
		$this->_select = $select;
		return $this;
	}

	/**
	 * @return \MongoCollection
	 */
	protected function getCollection()
	{
		/** @var $db \MongoDB  */
		$db = $this->getDb();
		return new \MongoCollection($db, self::getTable());
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
		return \MongoDBRef::create($this->_table, (is_object($this->id) ? $this->id : new \MongoId($this->id)));
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