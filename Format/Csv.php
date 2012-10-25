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
		fputcsv($this->file, __($headers)->map(array($this,'map_cell')), $this->separator);
	}

	public function addRow(array $row)
	{
		fputcsv($this->file, __($row)->map(array($this,'map_cell')), $this->separator);
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

	/**
	 * Note: Please make sure it's unused before removal.
	 * @param $cell
	 *
	 * @return string
	 */
	public function map_cell($cell)
	{
		if( $cell === false )
			return '0';
		if( $cell === null )
			return 'N/A';
		return $cell;
	}
}