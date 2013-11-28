<?php

class Lib_Db_Mongo_Select
{
	private $_fields = array();
	private $_query = array();
	private $_distinct = array();
	private $_skip = 0;
	private $_limit = null;
	private $_group = array();
	private $_order = array();

	public function clearColumns()
	{
		$this->_fields = array();
	}

	/**
	 * @todo $correlationName
	 * @param array $cols
	 * @param string $correlationName
	 */
	public function setColumns($cols, $correlationName)
	{
		foreach($cols as $key => $val)
		{
			$col = $key;
			$cval = $val;
			if(is_int($key))
			{
				$col = $val;
				$cval = true;
			}
			$this->_fields[$col] = $cval;
		}
	}

	/**
	 * @return array
	 */
	public function getColumns()
	{
		return $this->_fields;
	}

	/**
	 * @return array
	 */
	public function getConditions()
	{
		return $this->_query;
	}

	/**
	 * @param array $val mongo format conditions
	 */
	public function setConditions($val)
	{
		foreach($val as $key => $value)
		{
			if(array_key_exists($key, $this->_query))
			{
				if(is_array($this->_query[$key]) && is_array($value))
				{
					$this->_query[$key] = array_merge_recursive($this->_query[$key], $val);
				}
				elseif(is_array($this->_query[$key]))
				{
					$this->_query[$key] = $val;
				}
				//else do nothing, because already present condition is stronger than added
			}
			else
			{
				$this->_query[$key] = $value;
			}
		}
	}

	/**
	 * @param bool $flag
	 */
	public function setDistinct($flag)
	{
		$this->_distinct = $flag;
	}

	/**
	 * @param $spec
	 */
	public function group($spec)
	{
		if(!is_array($spec))
		{
			$spec = array($spec);
		}

		foreach($spec as $key => $val)
		{
			if(is_int($key))
				$key = $val;
			$this->_group[$key] = true;
		}
	}

	/**
	 * @return array
	 */
	public function getGroups()
	{
		return $this->_group;
	}

	/**
	 * @param int|null $count
	 * @param int|null $offset
	 */
	public function limit($count = null, $offset = null)
	{
		if(null !== $count)
		{
			$this->_limit = $count;
		}
		if(null !== $offset)
		{
			$this->_skip = $offset;
		}
	}

	/**
	 * @param int $skip
	 */
	public function setSkip($skip)
	{
		$this->_skip = $skip;
	}

	/**
	 * @return int
	 */
	public function getSkip()
	{
		return $this->_skip;
	}

	/**
	 * @param int $limit
	 */
	public function setLimit($limit)
	{
		$this->_limit = $limit;
	}

	/**
	 * @return int
	 */
	public function getLimit()
	{
		return $this->_limit;
	}

	/**
	 * @param array $order
	 */
	public function setOrder($order)
	{
		$this->_order = $order;
	}

	/**
	 * @return array
	 */
	public function getOrder()
	{
		return $this->_order;
	}
}
