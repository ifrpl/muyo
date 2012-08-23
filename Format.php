<?php

abstract class IFR_Main_Format
{
	protected $headers;
	protected $rows;
	private $title;
	private $extension;
	private $mime;



	/**
	 * @abstract
	 * @return string file contents
	 */
	abstract function getData();

	/**
	 * @abstract
	 * @return string
	 */
	abstract protected function getDefaultExtension();

	/**
	 * @abstract
	 * @return string
	 */
	abstract protected function getDefaultMime();


	/**
	 * @return string
	 */
	public function getExtension()
	{
		if ( !is_null($this->extension) )
		{
			return $this->extension;
		}
		else
		{
			return $this->getDefaultExtension();
		}
	}

	/**
	 * @param string $value
	 */
	public function setExtension($value)
	{
		$this->extension = $value;
	}

	/**
	 * @return string
	 */
	public function getMime()
	{
		if ( !is_null($this->mime) )
		{
			return $this->mime;
		}
		else
		{
			return $this->getDefaultMime();
		}
	}

	/**
	 * @param string $value
	 */
	public function setMime($value)
	{
		$this->mime = $value;
	}

	/**
	 * @param array $headers
	 */
	public function addHeaders(array $headers)
	{
		$this->headers []= $headers;
	}

	/**
	 * @param array $row
	 */
	public function addRow(array $row)
	{
		$this->rows []= $row;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $value
	 */
	public function setTitle($value)
	{
		$this->title = $value;
	}



	public static function getAll()
	{
		$ret = array();

		foreach( glob(__DIR__ . '/Format/*.php') as $format )
		{ /** @var IFR_Main_Format $format */

			$format = str_replace(array(__DIR__ . '/Format/', '.php'), '', $format);
			$class = "IFR_Main_Format_{$format}";

			$ret[$class] = new $class;
		}
		return $ret;
	}

	public static function getDefault()
	{
		return new IFR_Main_Format_Csv();
	}
}
