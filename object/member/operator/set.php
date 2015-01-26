<?php

namespace Lib\Object\Member\Operator;

/**
 * Sets a member to $value, and returns $value
 *
 * @package Lib\Object\Member\Operator
 * @see Lib\Object\Member\Operator\Store
 */
trait Set
{
	/**
	 * @param string $member
	 * @return mixed reference to a member
	 */
	abstract public function &member( $member );

	/**
	 * @param string $member
	 * @param mixed $value
	 * @return mixed value
	 */
	public function set( $member, $value )
	{
		$tmp = &$this->member( $member );
		$tmp = $value;
		return $value;
	}
}