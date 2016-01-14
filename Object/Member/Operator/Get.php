<?php

namespace IFR\Main\Object\Member\Operator;

/**
 * Returns a $value from $object identified by $member
 *
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