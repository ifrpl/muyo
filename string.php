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
 * @param string $needle
 *
 * @return callable
 */
function str_startswith_dg($needle)
{
	return function( $haystack )use($needle)
	{
		return str_startswith( $haystack, $needle );
	};
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
function str_truncate($string, $length = 200, $etc = '...', $break_words = false, $middle = false, $nobr = true)
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
function str_indent($string, $counter=1, $character = "\t")
{
	$pre = '';
	for($i = 0; $i < $counter; $i++)
	{
		$pre .= $character;
	}
	return implode(PHP_EOL,
		array_map(
			function ($str) use ($pre)
			{
				return $pre.$str;
			},
			explode(PHP_EOL, $string)
		)
	);
}

/**
 * @param int  $counter
 * @param string $character
 * @return callable
 */
function str_indent_dg( $counter=1, $character="\t" )
{
	return function()use($counter,$character)
	{
		$str = func_get_arg(0);
		return str_indent( $str, $counter, $character );
	};
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
 * Split $string by callable in 1-character chunks, appending matched chunk $before of part.
 *
 * @param string $string
 * @param callable $by
 * @param bool $before
 * @return array
 */
function str_splitter($string, $by, $before=false)
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
			if( !$before )
			{
				if( !empty($last) )
				{
					$ret []= $last;
				}
				$last = $char;
			}
			else
			{
				if( !empty($last) )
				{
					$ret []= $last.$char;
				}
				$last = '';
			}
		}
	}
	if( !empty($last) )
	{
		$ret []= $last;
	}
	return $ret;
}

/**
 * Map individual characters in string with $iterator
 *
 * @param string $string
 * @param callable $iterator
 * @return string
 */
function str_map($string,$iterator)
{
	return implode('',array_map_val(str_split($string),$iterator));
}

/**
 * Return delegate that maps individual characters in string with $iterator
 *
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
 * Return part of $string that follows first occurrence of $substring
 *
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
 * Return part of $string that precedes first occurrence of $substring
 *
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
 * Returns part of $string that starts on first occurrence of $substring ( which is included ).
 *
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
 * Returns part of string that ends on first occurrence of $substring ( which is included ).
 *
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
 * Wraps $string with another string ( or character )
 *
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
 * Returns delegate that wraps $string with another string ( or character )
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
 * Returns first n characters of $string
 *
 * @param string $str
 * @param int $first
 * @return string
 */
function str_first( $str, $first )
{
	debug_enforce_type( $str, 'string' );
	$first = min( $first, strlen($str) );
	if( $first === 0 )
	{
		$ret = "";
	}
	else
	{
		$ret = substr( $str, 0, $first );
		debug_enforce( $ret !== false );
	}
	return $ret;
}

/**
 * Returns last n characters of $string
 *
 * @param string $str
 * @param int $last
 * @return string
 */
function str_last( $str, $last )
{
	debug_enforce_type( $str, 'string' );
	$last = min( $last, strlen($str) );
	if( $last === 0 )
	{
		$ret = "";
	}
	else
	{
		$ret = substr( $str, -$last );
		debug_enforce( $ret !== false );
	}
	return $ret;
}

/**
 * @param string $str
 * @param int $n
 * @return string
 */
function str_from( $str, $n )
{
	debug_enforce_type( $str, 'string' );
	$length = strlen( $str );
	$n = min( $n, $length );
	return substr( $str, $n );
}

/**
 * @param string $str
 * @param int $n
 * @return string
 */
function str_to( $str, $n )
{
	debug_enforce_type( $str, 'string' );
	$length = strlen( $str );
	$n = min( $n, $length );
	return substr( $str, 0, -$n );
}

/**
 * @param string $str
 * @return int
 */
function str_to_uint( $str )
{
	debug_enforce_string( $str );
	debug_enforce( 0 !== strlen($str), "Cannot convert empty string to uint" );
	$ret = (int) $str;
	debug_enforce_gte( $ret, 0 );
	return $ret;
}

/**
 * @param string $separator
 * @return callable
 */
function str_explode_dg( $separator )
{
	return function ( $string ) use ( $separator )
	{
		return explode( $separator, $string );
	};
}