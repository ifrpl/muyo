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
}
