<?php

namespace Lib\Object\Member\Backend;

/**
 * @package Lib\Object\Member\Backend
 */
trait Arr
{
	private $arr = [];

	/**
	 * @param string $member
	 * @return mixed reference to a member
	 */
	public function &member( $member )
	{
		return $this->arr[ $member ];
	}
}