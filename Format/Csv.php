<?php

class IFR_Main_Format_Csv implements IFR_Main_Format
{
	private $extension = 'csv';
	private $mime = 'text/csv';

	private $file;

	public function __construct()
	{
		$this->file = fopen('php://temp/maxmemory:'. (12*1024*1024), 'r+');
	}

	public function __destruct()
	{
		if ( is_resource($this->file) )
			fclose($this->file);
	}

	/**
	 * @param array $headers
	 */
	public function addHeaders(array $headers)
	{
		fputcsv($this->file, $headers, ';');
	}

	/**
	 * @param array $row
	 */
	public function addRow(array $row)
	{
		fputcsv($this->file, $row, ';');
	}

	/**
	 * @return string file contents
	 */
	public function getData()
	{
		fflush($this->file);

		$ret = stream_get_contents($this->file, -1, 0);

		$this->__destruct();
		$this->__construct();

		return $ret;
	}

	/**
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
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
		return $this->mime;
	}

	/**
	 * @param string $value
	 */
	public function setMime($value)
	{
		$this->mime = $value;
	}
}