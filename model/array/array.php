<?php

if( !class_exists('Lib_Model') )
{
	require_once( implode(DIRECTORY_SEPARATOR, array(__DIR__,"..","model.php") ) );
}

/**
 * @package App
 * @subpackage Model
 */
class Lib_Model_Array extends Lib_Model
{

	/**
	 * Return row object for current id
	 *
	 * @return array
	 */
	public function getRow()
	{
		return array();
	}

	public function serialize()
	{
		return array(
			'model' => get_class($this),
			'data' => $this->serializeContent()
		);
	}

	public function serializeContent()
	{
		return array_map_val($this->recordColumnsGet(), function($row){
			if($row instanceof Lib_Model)
			{
				return $row->serialize();
			}
			return $row;
		});
	}

	/**
	 * @return $this
	 */
	public function debug()
	{
		debug($this->toArray());
		return $this;
	}

}