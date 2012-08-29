<?php

include 'IFR/PHPexcel/Classes/PHPExcel/Writer/Excel5.php';

class IFR_Main_Format_Excel extends IFR_Main_Format
{

	protected $phpexcelObj;
	protected $rowIndex = 1;

	public function __construct()
	{
		if(!PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_to_discISAM))
	    {
		    throw new Exception('Problem with creating cache');
	    }

		$this->phpexcelObj = new PHPExcel();
		$this->phpexcelObj->getProperties()->setTitle( $this->getTitle() );
	}

	protected function getDefaultExtension()
	{
		return 'xls';
	}

	protected function getDefaultMime()
	{
		return 'application/vnd.ms-excel';
	}

	public function addHeaders(array $headers)
	{
		$this->phpexcelObj->setActiveSheetIndex(0);
		$this->phpexcelObj->getActiveSheet()->fromArray(__::flatten($headers), null, 'A1');
		$this->rowIndex++;
	}

	public function addRow(array $row)
	{
		$this->phpexcelObj->setActiveSheetIndex(0);
		$sheet = $this->phpexcelObj->getActiveSheet();

		$colIndex = 0;
		foreach($row as $cellValue)
		{
			if ($cellValue != null)
			{
				$columnLetter = PHPExcel_Cell::stringFromColumnIndex($colIndex);
				$sheet->SetCellValue($columnLetter.$this->rowIndex, $cellValue);
			}
			$colIndex++;
		}
		$this->rowIndex++;
	}

	/**
	 * @return string file contents
	 * @throws Exception
	 */
	public function getData()
	{
		$objWriter = new PHPExcel_Writer_Excel5($this->phpexcelObj);

		$file = sys_get_temp_dir() . '/' . uniqid('export_excel5').'.cache';
		$objWriter->save($file);
		$data = file_get_contents($file);
		unlink($file);

		return $data;
	}

}