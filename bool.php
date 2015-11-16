<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)

if(!function_exists('boolval'))
{
	/**
	 * @param mixed $var
	 *
	 * @return bool
	 */
	function boolval($var)
	{
		return (bool)$var;
	}
}

if( !function_exists('boolval_dg') )
{
	/**
	 * @param callable|null $getter
	 *
	 * @return callable
	 */
	function boolval_dg($getter=null)
	{
		if( $getter===null )
		{
			$getter = tuple_get();
		}
		return function()use($getter)
		{
			return boolval( call_user_func_array( $getter, func_get_args() ) );
		};
	}
}