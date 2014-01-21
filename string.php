<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/**
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function str_endswith($haystack,$needle)
{
	return substr($haystack,-strlen($needle))==$needle;
}

/**
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function str_startswith($haystack,$needle)
{
	return substr($haystack,0,strlen($needle))==$needle;
}

/**
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function str_contains($haystack,$needle)
{
	return strpos($haystack,$needle)!==false;
}

/**
 * @param        $string
 * @param int    $length
 * @param string $etc
 * @param bool   $break_words
 * @param bool   $middle
 * @param bool   $nobr
 *
 * @return mixed|string
 */
function str_truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false, $nobr = true)
{
	if($length == 0)
	{
		return '';
	}

	$string = str_replace('&oacute;', "�", $string);
	if($nobr)
	{
		$string = preg_replace('/<br[\s]*?\/*?>/', ' ', $string);
	}

	if(strlen($string) > $length)
	{
		$length -= min($length, strlen($etc));
		if(!$break_words && !$middle)
		{
			$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1));
		}
		if(!$middle)
		{
			$string = substr($string, 0, $length);
			return substr($string, 0, strrpos($string, ' ')) . $etc;
		}
		else
		{
			return substr($string, 0, $length / 2) . $etc . substr($string, -$length / 2);
		}
	}
	else
	{
		return $string;
	}
}

/**
 * @param string $string
 * @param int $counter
 * @param string $character
 *
 * @return string
 */
function str_indent($string, $counter, $character = "\t")
{
	$pre = '';
	for($i = 0; $i < $counter; $i++)
	{
		$pre .= $character;
	}
	return implode(	PHP_EOL,
					array_map(	function($str) use ($pre) { return $pre.$str; },
								explode(PHP_EOL, $string)));
}

/**
 * Is last char/entry escaped?
 * @param string|array $subject
 * @param string|mixed|null $by if null, defaults to last $subject character/entry
 * @return bool
 */
function ifr_escaped($subject, $by = null)
{
	if ( is_string($subject) )
	{
		$cnt = strlen($subject);
	}
	else
	{
		$cnt = count($subject);
	}

	if ( is_null($by) )
	{
		$by = $subject[$cnt-1];
	}

	$ret = false;
	for( $i = $cnt-2; $i >= 0; $i-- )
	{
		if ( $subject[$i] !== $by )
		{
			return $ret;
		}
		else
		{
			$ret = !$ret;
		}
	}

	return $ret;
}

/**
 * @param string $expr
 * @param string $replacement
 * @param string $subject
 *
 * @return string
 */
function preg_replace_r($expr, $replacement, $subject)
{
	do
	{
		$old = $subject;
		$subject = preg_replace($expr, $replacement, $subject);
	} while ( $old !== $subject );
	return $subject;
}

/**
 * Generates pseudo-random 7bit US-ASCII string
 * @param int $length
 * @param callable $allowed
 * @return string
 */
function str_ascii7_prand($length = 1, $allowed = null)
{
	if( null === $allowed )
	{
		$allowed = function($char){ return true; };
	}
	$ret = '';
	for ($i = 0; $i < $length; $i++)
	{
		do {
			$char = chr(rand(0,127));
		} while( !$allowed($char) );
		$ret .= $char;
	}
	return $ret;
}

/**
 * @param string $string
 * @param callable|string|int $by
 * @return array
 */
function str_splitter($string, $by)
{
	$ret = array();
	$last = '';
	$length = strlen($string);
	for( $i=0; $i<$length; $i++ )
	{
		$char = $string[$i];
		if( !$by($char) )
		{
			$last .= $char;
		}
		else
		{
			if( !empty($last) )
			{
				$ret []= $last;
			}
			$last = $char;
		}
	}
	if( !empty($last) )
	{
		$ret []= $last;
	}
	return $ret;
}

/**
 * @param string $string
 * @param callable $iterator
 * @return array
 */
function str_map($string,$iterator)
{
	return implode('',array_map_val(str_split($string),$iterator));
}

/**
 * @param callable $iterator
 * @return callable
 */
function str_map_dg($iterator)
{
	return function($string)use($iterator)
	{
		return str_map($string,$iterator);
	};
}

/**
 * @param string $string
 * @param string $substring
 * @return null|string
 */
function str_find_after($string, $substring)
{
	debug_enforce_type( $string, 'string' );
	debug_enforce_type( $substring, 'string' );

	$thisLen = strlen( $substring );
	$thisAndAfter = strstr( $string, $substring, false );
	if( $thisAndAfter===false )
	{
		return null;
	}
	$after = substr( $thisAndAfter, $thisLen );
	return $after;
}

/**
 * @param string $string
 * @param string $substring
 * @return null|string
 */
function str_find_before($string, $substring)
{
	debug_enforce_type( $string, 'string' );
	debug_enforce_type( $substring, 'string' );

	$before = strstr( $string, $substring, true );
	if( $before===false )
	{
		return null;
	}
	else
	{
		return $before;
	}
}

/**
 * @param $string
 * @param $substring
 * @return string
 */
function str_find_from( $string, $substring )
{
	debug_enforce_type( $string, 'string' );
	debug_enforce_type( $substring, 'string' );

	$from = strstr( $string, $substring, false );
	if( $from===false )
	{
		return null;
	}
	else
	{
		return $from;
	}
}

/**
 * @param $string
 * @param $substring
 * @return string
 */
function str_find_to( $string, $substring )
{
	debug_enforce_type( $string, 'string' );
	debug_enforce_type( $substring, 'string' );

	$thisPos = strpos( $string, $substring );

	if( $thisPos===false )
	{
		return null;
	}

	$to = substr( $string, 0, $thisPos );
	return $to===false ? null : $to;
}

/**
 * @param string $string
 * @param string $with
 * @return string
 */
function str_wrap( $string, $with )
{
	debug_enforce_type( $string, 'string' );
	debug_enforce_type( $with, 'string' );

	return $with.$string.$with;
}

/**
 * @param string $with
 * @return callable
 */
function str_wrap_dg( $with )
{
	return function( $string )use( $with )
	{
		return str_wrap( $string, $with );
	};
}

/**
 * @param string $str
 * @param int $first
 * @return string
 */
function str_first( $str, $first )
{
	debug_enforce_type( $str, 'string' );
	debug_enforce( intval($first) > 0, $first );
	$ret = substr( $str, 0, $first );
	debug_enforce( $ret !== false );
}

/**
 * @param string $str
 * @param int $last
 * @return string
 */
function str_last( $str, $last )
{
	debug_enforce_type( $str, 'string' );
	debug_enforce( intval($last) > 0, $last );
	$ret = substr( $str, -$last );
	debug_enforce( $ret !== false );
}