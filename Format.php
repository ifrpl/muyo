<?php

abstract class IFR_Main_Format
{
	protected $headers;
	protected $rows;
	private $title;
	private $extension;
	private $mime;

	protected $_cache_handler;
	protected $_cache_dir;
	protected $_cache_objects;
	protected $_cache_ptr;

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
	 * @param string $dir
	 *
	 * @return IFR_Main_Format
	 */
	public function setCacheDir($dir)
	{
		$this->_cache_dir = $dir;
		return $this;
	}

	public function getCacheHandler()
	{
		if(!$this->_cache_handler)
		{
			if(is_null($this->_cache_dir))
			{
				$this->_cache_dir = APPLICATION_PATH . '/../data/tmp/';
			}

			$file = $this->_cache_dir . uniqid('cache_format');
			file_put_contents($file, '');
			$this->_cache_handler = fopen($file, 'w+');
		}
		return $this->_cache_handler;
	}

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
		fseek($this->getCacheHandler(), 0, SEEK_END);
		$offset = ftell($this->getCacheHandler());
		fwrite($this->getCacheHandler(), serialize($row));

		$this->_cache_objects[$this->_cache_ptr] = array(
			'ptr' => $offset,
			'size' => ftell($this->getCacheHandler()) - $offset
		);

		$this->_cache_ptr++;
	}

	public function readRow()
	{
		if(!isset($this->_cache_objects[$this->_cache_ptr]))
		{
			return false;
		}

		$obj = $this->_cache_objects[$this->_cache_ptr];

		$this->_cache_ptr++;

		fseek($this->getCacheHandler(), $obj['ptr']);
		return unserialize( fread($this->getCacheHandler(), $obj['size']) );
	}

	public function resetPointer()
	{
		$this->_cache_ptr = 0;
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
