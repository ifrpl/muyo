<?php

class IFR_Main_Format_Csv extends IFR_Main_Format
{
	private $file;
	public $separator = ';';

	public function __construct()
	{
		$this->file = fopen('php://temp/maxmemory:'. (12*1024*1024), 'r+');
	}

	public function __destruct()
	{
		if ( is_resource($this->file) )
			fclose($this->file);
	}

	protected function getDefaultExtension()
	{
		return 'csv';
	}

	protected function getDefaultMime()
	{
		return 'text/csv';
	}

	public function addHeaders(array $headers)
	{
		fputcsv($this->file, $headers, $this->separator);
	}

	public function addRow(array $row)
	{
		fputcsv($this->file, $row, $this->separator);
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

		// HACK for MS Excel
		$ret =  chr(255).chr(254).iconv('UTF-8', 'UTF-16LE', $ret);

		return $ret;
	}
}