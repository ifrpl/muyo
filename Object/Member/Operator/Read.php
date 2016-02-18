<?php

namespace IFR\Main\Object\Member\Operator;

/**
 * Reads a $member to $value and returns $this
 *
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