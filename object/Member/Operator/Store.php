<?php

namespace Lib\Object\Member\Operator;

/**
 * Sets a member to $value and returns $this
 *
 * @package Lib\Object\Member\Operator
 * @see Lib\Object\Member\Operator\Set
 */
trait Store
{
	/**
	 * @param string $member
	 * @return mixed reference to a member
	 */
	abstract public function &member( $member );

	/**
	 * @param string $member
	 * @param mixed $value
	 * @return $this
	 */
	public function store( $member, $value )
	{
		$tmp = &$this->member( $member );
		$tmp = $value;
		return $this;
	}
}