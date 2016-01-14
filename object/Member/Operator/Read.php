<?php

namespace Lib\Object\Member\Operator;

/**
 * Reads a $member to $value and returns $this
 *
 * @package Lib\Object\Member\Operator
 * @see Lib\Object\Member\Operator\Get
 */
trait Read
{
	/**
	 * @param string $member
	 * @return mixed reference to a member
	 */
	abstract public function &member( $member );

	/**
	 * @param string $member
	 * @param mixed &$target
	 * @return $this
	 */
	public function read( $member, &$target )
	{
		$target = $this->member( $member );
		return $this;
	}
}