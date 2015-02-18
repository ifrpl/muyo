<?php

namespace Lib\Object;

trait TypeInfo
{
	/** @var string */
	private $class;

	public function __construct()
	{
		$this->class = get_called_class();
	}
}