<?php

namespace Lib\Object\Member\Operator;

/**
 * Returns a $value from $object identified by $member
 *
 * @package Lib\Object\Member\Operator
 * @see Lib\Object\Member\Operator\Read
 */
trait Get
{
	/**
	 * @param string $member
	 * @return mixed reference to a member
	 */
	abstract public function &member( $member );

	/**
	 * @param string $member
	 * @return mixed
	 */
	public function get( $member )
	{
		return $this->member( $member );
	}
}