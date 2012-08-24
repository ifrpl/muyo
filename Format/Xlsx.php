<?php

include 'IFR/PHPexcel/Classes/PHPExcel/Writer/Excel2007.php';

class IFR_Main_Format_Xlsx extends IFR_Main_Format
{
	protected function getDefaultExtension()
	{
		return 'xlsx';
	}

	protected function getDefaultMime()
	{
		return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
	}

	/**
	 * @return string file contents
	 * @throws Exception
	 */
	public function getData()
	{
	    if(!PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_to_discISAM))
	    {
		    throw new Exception('Problem with creating cache');
	    }

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setTitle( $this->getTitle() );

		$objPHPExcel->setActiveSheetIndex(0);
		$sheet = $objPHPExcel->getActiveSheet();

		$sheet->fromArray(__::flatten($this->headers), null, 'A1');

		$rowIndex = 2;
		while($row = $this->readRow())
		{
			$colIndex = 0;
			foreach($row as $cellValue)
			{
				if ($cellValue != null)
				{
					$columnLetter = PHPExcel_Cell::stringFromColumnIndex($colIndex);
					$sheet->SetCellValue($columnLetter.$rowIndex, $cellValue);
				}
				$colIndex++;
			}
			$rowIndex++;
		}

		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

		$file = sys_get_temp_dir() . '/' . uniqid('export_excel').'.cache';
		$objWriter->save($file);
		$data = file_get_contents($file);
		unlink($file);

		return $data;
	}

}