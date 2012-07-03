<?php

class IFR_Main_Format_Csv
{
	public $extension = 'csv';
	public $mime = 'text/csv';

	private $file;

	public function __construct()
	{
		$this->file = fopen('php://temp/maxmemory:'. (12*1024*1024), 'r+');
	}

	public function __destruct()
	{
		fclose($this->file);
	}

	/**
	 * @param array $headers
	 */
	public function addHeaders(array $headers)
	{
		fputcsv($this->file, $headers);
	}

	/**
	 * @param array $row
	 */
	public function addRow(array $row)
	{
		fputcsv($this->file, $row);
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
}