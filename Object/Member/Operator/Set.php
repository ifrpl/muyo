<?php

namespace IFR\Main\Object\Member\Operator;

/**
 * Sets a member to $value, and returns $value
 *
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