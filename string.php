<?php

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
	return implode(PHP_EOL, array_map(function($str) use ($pre) { return $pre.$str; },explode(PHP_EOL,$string)));
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