<?php

class IFR_Main_Format_Xls extends IFR_Main_Format
{
	protected function getDefaultExtension()
	{
		return 'xls';
	}

	protected function getDefaultMime()
	{
		return 'application/vnd.ms-excel';
	}

	/**
	 * @return string file contents
	 */
	public function getData()
	{
		$xml = '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?>';

		$xml .= '<Workbook xmlns:x="urn:schemas-microsoft-com:office:excel"'
			. 'xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
			. 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';

		$xml .= '<Worksheet ss:Name="' . $this->getTitle() . '" ss:Description="' . $this->getTitle() . '"><ss:Table>';
		$xml .= $this->serializeRow(__::flatten($this->headers));
		foreach( $this->rows as $row)
		{
			$xml .= $this->serializeRow($row);
		}
		$xml .= '</ss:Table></Worksheet>';

		$xml .= '</Workbook>';

		return $xml;
	}

	private function serializeRow($row)
	{
		$xml = '<ss:Row>';
		foreach( $row as $value )
		{
			$type = !is_numeric($value) ? 'String' : 'Number';

			$xml .= '<ss:Cell><Data ss:Type="' . $type . '">' . $value . '</Data></ss:Cell>';
		}
		$xml .= '</ss:Row>';
		return $xml;
	}

	/**
	 * @param array $row
	 */
	public function addRow(array $row)
	{
		if ( is_array($this->rows) && count($this->rows) > 65569 )
		{
      throw new App_Exception('Maximum number of records in xls format is 65569');
  	}
		else
		{
			parent::addRow($row);
		}
	}
}