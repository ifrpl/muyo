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
 * @return callable
 */
function str_startswith_dg( $needle )
{
	return function( $haystack )use( $needle )
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
 * @param callable|string $needle
 * @param callable|string|null $haystack
 * @return callable
 */
function str_contains_dg( $needle, $haystack=null )
{
	if( null===$haystack )
	{
		$haystack = tuple_get(0);
	}
	elseif( is_string($haystack) )
	{
		$haystack = return_dg( $haystack );
	}
	else
	{
		debug_assert_type( $haystack, 'callable' );
	}
	if( is_string($needle) )
	{
		$needle = return_dg( $needle );
	}
	else
	{
		debug_assert_type( $haystack, 'callable' );
	}

	return function()use($needle,$haystack)
	{
		$args = func_get_args();
		return str_contains(
			call_user_func_array( $haystack, $args ),
			call_user_func_array( $needle, $args )
		);
	};
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
 * @param int  $length
 * @param bool $special_chars
 * @param bool $extra_special_chars
 *
 * @return string
 */
function str_random( $length = 12, $special_chars = true, $extra_special_chars = false )
{
	$allowed = ctype_alnum_dg();
	if( true === $special_chars )
	{
		$allowed = or_dg( $allowed, ctype_special_dg() );
	}
	if( true === $extra_special_chars )
	{
		$allowed = or_dg( $allowed, ctype_special_extra_dg() );
	}
	return str_ascii7_prand( $length, $allowed );
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
 * @param callable $by
 * @param bool $before
 * @return callable
 */
function str_splitter_dg( $by, $before=false )
{
	return function( $string )use($by,$before)
	{
		return str_splitter( $string, $by, $before );
	};
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
 * @param string $string
 * @param callable $iterator
 *
 * @return bool
 */
function str_all( $string, $iterator )
{
	debug_assert_type( $string, 'string' );
	return array_all( str_split( $string ), $iterator );
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
 * @param string $substring
 * @param callable|null $string
 * @return callable
 */
function str_find_after_dg( $substring, $string=null )
{
	if( $string===null )
	{
		$string = tuple_get( 0 );
	}
	return function()use($string,$substring)
	{
		return str_find_after( call_user_func_array( $string, func_get_args() ), $substring );
	};
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
 * @param string $substring
 * @param callable|null $string
 * @return callable
 */
function str_find_before_dg( $substring, $string=null )
{
	if( $string===null )
	{
		$string = tuple_get( 0 );
	}
	return function()use($string,$substring)
	{
		return str_find_before( call_user_func_array( $string, func_get_args() ), $substring );
	};
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

/**
 * @param string $expression
 * @param bool $extended
 * @return string
 */
function str_glob2regexp( $expression, $extended=false )
{
	$length = strlen( $expression );
	$ret = "";
	for ($i = 0; $i < $length; $i++)
	{
		$c = $expression[ $i ];
		switch( $c )
		{
			case '\\':
			case '/':
			case '$':
			case '^':
			case '+':
			case '.':
			case '(':
			case ')':
			case '=':
			case '!':
			case '|':
				$ret .= "\\{$c}";
			break;
			case '?':
				if( $extended )
				{
					$ret .= ".";
				}
			break;
			case '[':
			case ']':
				if( $extended )
				{
					$ret .= $c;
				}
			break;
			case '{':
				if( $extended )
				{
					$ret .= '(';
				}
			break;
			case '}':
				if( $extended )
				{
					$ret .= ')';
				}
			break;
			case ',':
				if( $extended )
				{
					$ret .= '|';
				}
				$ret .= "\\{$c}";
			break;
			case '*':
				$ret .= '(.*)';
			break;
			default:
				$ret .= $c;
			break;
		}
	}
	return "/^{$ret}$/";
}



/**
 * @param string $separator
 * @return callable
 */
function string_explode_dg( $separator )
{
	return function( $string )use($separator)
	{
		return explode( $separator, $string );
	};
}

/**
 * @param string $string
 * @param string $delimiter
 * @param string $enclosure
 * @param string $escape
 * @throws Exception
 * @return array
 */
function str_to_csv_assoc( $string, $delimiter=',', $enclosure='"', $escape='\\' )
{
	$resource = str_to_res( $string );
	$finally = function()use( $resource )
	{
		fclose( $resource );
	};
	try
	{
		$ret = res_to_csv_assoc( $resource, $delimiter, $enclosure, $escape );
	}
	catch( Exception $e )
	{
		$finally();
		throw $e;
	}
	$finally();
	return $ret;
}

/**
 * @param string $string
 * @return resource
 */
function str_to_res( $string )
{
	$stream = fopen( 'php://memory', 'w+' );
	fwrite( $stream, $string );
	rewind( $stream );
	return $stream;
}

/**
 * @param resource $resource
 * @param string $delimiter
 * @param string $enclosure
 * @param string $escape
 * @return array
 */
function res_to_csv_assoc( $resource, $delimiter=',', $enclosure='"', $escape='\\' )
{
	$ret = array();
	$header = fgetcsv( $resource, 0, $delimiter, $enclosure, $escape );
	if( debug_assert( $header !== false ) )
	{
		while( true )
		{
			$row = fgetcsv( $resource, 0, $delimiter, $enclosure, $escape );
			if( $row === false )
			{
				break;
			}
			else
			{
				$ret []= array_map_key( $row, function( $value, $key )use( $header )
				{
					return $header[ $key ];
				});
			}
		}
	}
	return $ret;
}

/**
 * @return callable
 */
function strcasecmp_dg()
{
	return function( $arg1, $arg2 )
	{
		return strcasecmp( $arg1, $arg2 );
	};
}

/**
 * @return callable
 */
function str_lcfirst_dg()
{
	return function( $val )
	{
		return lcfirst( $val );
	};
}

/**
 * @return callable
 */
function strtoupper_dg()
{
	return function( $val )
	{
		return strtoupper( $val );
	};
}

/**
 * @param string $what
 * @return callable
 */
function str_prepend_dg( $what )
{
	return function( $string )use( $what )
	{
		return $what.$string;
	};
}

/**
 * @param callable|string $what
 * @param callable|string|int null
 * @return callable
 */
function str_append_dg( $what, $string=null )
{
	if( !is_callable($what) )
	{
		$what = return_dg( $what );
	}
	if( is_null($string) )
	{
		$string = tuple_get(0);
	}
	elseif( !is_callable($string) )
	{
		$string = return_dg( $string );
	}
	return function( $string )use( $what,$string )
	{
		$args = func_get_args();
		return call_user_func_array( $string,$args ).call_user_func_array( $what,$args );
	};
}


/**
 * @param string $string
 * @return string
 */
function decamelize( $string )
{
    return preg_replace(
        '/(^|[a-z])([A-Z])/e',
        'strtolower(strlen("\\1") ? "\\1_\\2" : "\\2")',
        $string
    );
}

/**
 * @param callable|null $getter
 * @return callable
 */
function ucfirst_dg( $getter=null )
{
	if( $getter===null )
	{
		$getter = tuple_get( 0 );
	}
	return function()use($getter)
	{
		return ucfirst( call_user_func_array( $getter, func_get_args() ) );
	};
}

/**
 * @param string $string
 * @return string
 */
function str_reverse( $string )
{
	return strrev( $string );
}

/**
 * @param callback|null $string
 * @return callable
 */
function str_reverse_dg( $string=null )
{
	if( $string===null )
	{
		$string = tuple_get( 0 );
	}
	return function()use($string)
	{
		return str_reverse( call_user_func_array( $string, func_get_args() ) );
	};
}

/**
 * @param callable|string $search
 * @param callable|string $replace
 * @param callable|null $subject
 *
 * @return callable
 */
function str_replace_dg( $search, $replace, $subject=null )
{
	if( is_string($search) )
	{
		$search = return_dg( $search );
	}
	if( is_string($replace) )
	{
		$replace = return_dg( $replace );
	}
	if( is_null($subject) )
	{
		$subject = tuple_get(0);
	}
	return function()use($search,$replace,$subject)
	{
		$args = func_get_args();
		return str_replace( call_user_func_array($search,$args), call_user_func_array($replace,$args), call_user_func_array($subject,$args) );
	};
}

/**
 * @return callable
 */
function ctype_lower_dg()
{
	return function($char)
	{
		return ctype_lower($char);
	};
}

/**
 * @return callable
 */
function ctype_xdigit_dg()
{
	return function($char)
	{
		return ctype_xdigit($char);
	};
}

/**
 * @return callable
 */
function ctype_alnum_dg()
{
	return function($char)
	{
		return ctype_alnum($char);
	};
}

/**
 * @param string|callable|null $string
 * @return callable
 */
function strtolower_dg( $string=null )
{
	if( is_string($string) )
	{
		$string = return_dg( $string );
	}
	elseif( $string===null )
	{
		$string = tuple_get();
	}
	return function()use($string)
	{
		return strtolower( call_user_func_array($string,func_get_args()) );
	};
}

/**
 * @param string $string
 * @return mixed
 */
function ctype_special( $string )
{
	debug_assert_type( $string, 'string' );
	return str_all( $string, str_contains_dg( tuple_get(), '!@#$%^&*()' ) );
}

/**
 * @return callable
 */
function ctype_special_dg()
{
	return function($string)
	{
		return ctype_special( $string );
	};
}

/**
 * @param string $string
 * @return callable
 */
function ctype_special_extra( $string )
{
	debug_assert_type( $string, 'string' );
	return str_all(
		$string,
		str_contains_dg( tuple_get(), '-_ []{}<>~`+=,.;:/?|' )
	);
}

/**
 * @return callable
 */
function ctype_special_extra_dg()
{
	return function( $string )
	{
		return ctype_special_extra( $string );
	};
}

/**
 * @param string $string
 * @param string $substring
 * @return int
 */
function str_count($string,$substring)
{
	$strlen = strlen( $string );
	$count = 0;
	for ($i = 0; $i < $strlen; $i++)
	{
		if( str_startswith( str_from( $string, $i ), $substring ) )
		{
			$count++;
		}
	}
	return $count;
}