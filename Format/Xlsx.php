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
		$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_discISAM;
	    $cacheSettings = array(
		    'dir'  => APPLICATION_PATH . '/../data/tmp/'
	    );
	    if(!PHPExcel_Settings::setCacheStorageMethod($cacheMethod,$cacheSettings))
	    {
		    throw new Exception('Problem with creating cache');
	    }

		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setTitle( $this->getTitle() );

		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->fromArray(__::flatten($this->headers), null, 'A1');
		$objPHPExcel->getActiveSheet()->fromArray($this->rows, null, 'A2');

		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

		$file = APPLICATION_PATH . '/../data/tmp/' . uniqid('export_excel');
		$objWriter->save($file);
		$data = file_get_contents($file);
		unlink($file);

		return $data;
	}

}