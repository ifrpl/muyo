<?php

namespace Lib\Object\Member\Backend;

/**
 * @package Lib\Object\Member\Backend
 */
trait Property
{
	/**
	 * @param string $member
	 * @return mixed reference to a member
	 */
	public function &member( $member )
	{
		return $this->{$member};
	}
}