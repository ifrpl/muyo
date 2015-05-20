<?php

/**
 * @package App
 *
 * @property int id
 */
class Lib_Model_Set implements Iterator
{

	/**
	 * @var array
	 */
	protected $_resultSet = array();

	/**
	 * @var Lib_Model
	 */
	protected $_modelObject = null;

	public function getResultSet()
	{
		return $this->_resultSet;
	}

	public function setResultSet(array $set)
	{
		$this->_resultSet = $set;
		return $this;
	}

	public function setModel(Lib_Model $model)
	{
		$this->_modelObject = $model;
		return $this;
	}

	/**
	 * @return Lib_Model
	 */
	protected function getModel()
	{
		return $this->_modelObject;
	}

	protected function _getModelWithData($data)
	{
		$model = $this->getModel();
		$model->unserializeContent($data);
		$model->changedColumnsReset();

		return $model;
	}

	/**
	 * @return mixed
	 */
	public function current()
	{
		$data = current($this->_resultSet);
		return $this->_getModelWithData($data);
	}

	/**
	 * @return void
	 */
	public function next()
	{
		next($this->_resultSet);
	}

	/**
	 * @return mixed
	 */
	public function key()
	{
		return key($this->_resultSet);
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		$key = $this->key();
		return array_key_exists($key, $this->_resultSet);
	}

	public function rewind()
	{
		reset($this->_resultSet);
	}

	/**
	 * @return int
	 */
	public function count()
	{
		return count($this->_resultSet);
	}

	/**
	 * @param string $property
	 * @return array
	 */
	public function pluck( $property )
	{
		$ret = [];
		for ( $this->rewind(); $this->valid(); $this->next() )
		{
			$ret [ $this->key() ] = $this->current()->{$property};
		}
		return $ret;
	}

	/**
	 * @param callable $callable
	 * @return array
	 */
	public function map( $callable )
	{
		$ret = [];
		for( $this->rewind(); $this->valid(); $this->next() )
		{
			$key = $this->key();
			$ret [ $key ] = $callable( $this->current(), $key );
		}
		return $ret;
	}

	/**
	 * @param callable $callable
	 * @return bool
	 */
	public function all($callable)
	{
		$ret = true;
		for( $this->rewind(); $this->valid(); $this->next() )
		{
			if( !$callable( $this->current(), $this->key() ) )
			{
				$ret = false;
				break;
			}
		}
		return $ret;
	}

	/**
	 * @param callable $callable
	 * @return bool
	 */
	public function any($callable)
	{
		$ret = false;
		for( $this->rewind(); $this->valid(); $this->next() )
		{
			if( $callable( $this->current(), $this->key() ) )
			{
				$ret = true;
				break;
			}
		}
		return $ret;
	}

	/**
	 * Warning: unusual reduce implementation
	 *
	 * @param callable $callable
	 * @return bool
	 */
	public function reduce($callable)
	{
		$ret = true;
		$carry = null;
		for( $this->rewind(); $this->valid(); $this->next() )
		{
			$model = $this->current();
			if( $carry !== null && !$callable($carry,$model) )
			{
				$ret = false;
				break;
			}
			$carry = $model;
		}
		return $ret;
	}

	/**
	 * @param callable $callable
	 * @return $this
	 */
	public function filter( $callable )
	{
		$ret = [];
		for( $this->rewind(); $this->valid(); $this->next() )
		{
			$value = $this->current();
			$key = $this->key();
			if( $callable( $value, $key ) )
			{
				$ret[ $key ] = $value;
			}
		}
		return $this->setResultSet( $ret );
	}

	/**
	 * @param callable $callable
	 */
	public function each( $callable )
	{
		for( $this->rewind(); $this->valid(); $this->next() )
		{
			$callable( $this->current(), $this->key() );
		}
	}
}
