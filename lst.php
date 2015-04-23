<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)

require_once __DIR__.'/debug.php';
require_once __DIR__.'/misc.php';


if( !function_exists('is_array_list') )
{
	/**
	 * @param mixed $val
	 * @return bool
	 */
	function is_array_list($val)
	{
		if( !is_array($val) )
		{
			$ret = false;
		}
		else
		{
			if( empty($val) )
			{
				$ret = true; // WARNING: common for both type of arrays
			}
			else
			{
				$ret = array_some($val,tuple_get(1,'is_int'));
			}
		}
		return $ret;
	}
}

if( !function_exists('list_uniq') )
{
	/**
	 * Warning: Opposed to {@see array_uniq} it doesn't preserve original keys
	 *
	 * @param array $list
	 * @param callable $iterator
	 *
	 * @return array
	 */
	function list_uniq($list, $iterator)
	{
		return array_values(array_uniq($list,$iterator));
	}
}

if( !function_exists('list_filter_key') )
{
	/**
	 * @param array $list
	 * @param callable $filter
	 * @return array
	 */
	function list_filter_key( $list, $filter )
	{
		return array_values( array_filter_key( $list, $filter ) );
	}
}

if( !function_exists('list_filter_key_dg') )
{
	/**
	 * @param callable $filter
	 * @param callable|array|null $list
	 * @return callable
	 */
	function list_filter_key_dg( $filter, $list=null )
	{
		if( null===$list )
		{
			$list = tuple_get();
		}
		elseif( is_array_list($list) )
		{
			$list = return_dg( $list );
		}
		else
		{
			debug_assert_type( $list, 'callable' );
		}
		debug_assert_type( $filter, 'callable' );
		return function()use( $list, $filter )
		{
			$args = func_get_args();
			$list = call_user_func_array( $list, $args );
			return list_filter_key(
				$list,
				$filter
			);
		};
	}
}