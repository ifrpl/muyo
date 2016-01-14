<?php

namespace IFR\Main\Model;

/**
 * @package App
 * @subpackage Model
 */
class Array_ extends \IFR\Main\Model
{

	/**
	 * Return row object for current id
	 *
	 * @return array
	 */
	public function getRow()
	{
		return [];
	}

	public function serialize()
	{
		return [
			'model' => get_class($this),
			'data' => $this->serializeContent()
		];
	}

	public function serializeContent()
	{
		return array_map_val($this->recordColumnsGet(), function($row){
			if($row instanceof \IFR\Main\Model)
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

	protected function isColumnSetLocally($name)
	{

	}

	public function clearColumns($clearPK = false)
	{

	}

	public function getAlias()
	{
		return null;
	}
}