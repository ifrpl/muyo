<?php

interface IFR_Main_Format
{
	/**
	 * @param array $headers
	 */
	public function addHeaders(array $headers);

	/**
	 * @param array $row
	 */
	public function addRow(array $row);

	/**
	 * @return string file contents
	 */
	public function getData();



	/**
	 * @return string
	 */
	public function getExtension();

	/**
	 * @param string $value
	 */
	public function setExtension($value);



	/**
	 * @return string
	 */
	public function getMime();

	/**
	 * @param string $value
	 */
	public function setMime($value);
}
