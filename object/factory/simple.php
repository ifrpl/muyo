<?php

namespace Lib\Object\Instance;

trait Simple
{
	public static function instance()
	{
		return new static;
	}
}