<?php

class Doctype
{
	const VER_HTML_4_01       = 0;
	const VER_XHTML_1_0       = 1;
	const VER_XHTML_1_1       = 2;
	const VER_XHTML_BASIC_1_1 = 3;
	const VER_HTML_5          = 4;
	const VER_MATH_ML_1_01    = 5;
	//FIXME: compound support
	public static $ver = array(
		self::VER_HTML_4_01,
		self::VER_XHTML_1_0,
		self::VER_XHTML_1_1,
		self::VER_XHTML_BASIC_1_1,
		self::VER_HTML_5,
		self::VER_MATH_ML_1_01,
	);
	private static $value=self::VER_HTML_5;

	/**
	 * @param int $value
	 */
	public static function set( $value )
	{
		if( debug_assert_array_contains( self::$ver, $value ) )
		{
			self::$value = $value;
		}
	}

	/**
	 * @return int
	 */
	public static function get()
	{
		return self::$value;
	}

	/**
	 * @param int $version
	 * @return bool
	 */
	public static function equals( $version )
	{
		return debug_assert_array_contains( self::$ver, $version ) && self::$value===$version;
	}

	/**
	 * @return bool
	 */
	public static function isXhtml()
	{
		return array_contains(
			array(self::VER_XHTML_1_0, self::VER_XHTML_1_1, self::VER_XHTML_BASIC_1_1),
			self::get()
		);
	}
}

if( !function_exists('html_doctype_set') )
{
	/**
	 * @param int $version
	 * @see Doctype::$ver
	 */
	function html_doctype_set($version)
	{
		Doctype::set( $version );
	}
}

if( !function_exists('html_doctype_get') )
{
	/**
	 * @return int
	 * @see Doctype::$ver
	 */
	function html_doctype_get()
	{
		return Doctype::get();
	}
}

if( !function_exists('html_tag') )
{
	/**
	 * @param string $name
	 * @param array $attribute
	 * @param string $content
	 * @return string
	 */
	function html_tag( $name, $attribute, $content )
	{
		$attr = array_chain(
			$attribute,
			array_map_val_dg( html_attribute_dg( tuple_get(1), tuple_get(0) ) ),
			array_implode_dg(' ')
		);
		if( empty($content) )
		{
			if( Doctype::isXhtml() )
			{
				$ret = "<{$name}{$attr}/>";
			}
			else
			{
				$ret = "<{$name}{$attr}>";
			}
		}
		else
		{
			$ret = "<{$name}{$attr}>{$content}</{$name}>";
		}
		return $ret;
	}
}

if( !function_exists('html_attribute') )
{
	/**
	 * @param string $name
	 * @param mixed $value
	 * @return string|null
	 */
	function html_attribute( $name, $value )
	{
		$attrChain = array(
			array_filter_key_dg(function( $val, $key )
			{
				$skip = $val === false;
				return !$skip;
			})
		);
		if( Doctype::isXhtml() )
		{
			$attrChain []= array_map_val_dg(function( $val, $key )
			{
				if( true === $val )
				{
					$val = $key;
				}
				return $val;
			});
		}
		$attrChain []= array_map_key_dg(function( $val, $key )
		{
			return preg_replace( '/([\t\n\f \/>"\'=]+)/', '', $key );
		});
		$flags = htmlspecialchars_flags();
		$attrChain []= array_map_val_dg(function( $val, $key )use( $flags )
		{
			$val = str_wrap( htmlspecialchars( $val, ENT_QUOTES|$flags ), '"' );
			return " {$key}={$val}";
		});
		$ret = call_user_func_array( 'array_chain', array_merge( [[$name=>$value]], $attrChain ) );
		if( empty($ret) )
		{
			$ret = null;
		}
		else
		{
			$ret = array_shift($ret);
		}
		return $ret;
	}
}

if( !function_exists('html_attribute_dg') )
{
	/**
	 * @param string $name
	 * @param string $value
	 * @return callable
	 */
	function html_attribute_dg( $name, $value )
	{
		if( is_string($name) )
		{
			$name = return_dg($name);
		}
		elseif( is_null($name) )
		{
			$name = tuple_get(1);
		}
		else
		{
			debug_enforce_type( $name, 'callable' );
		}
		if( is_null($value) )
		{
			$value = tuple_get(0);
		}
		elseif( !is_callable($value) )
		{
			$value = return_dg($value);
		}
		return function()use($name,$value)
		{
			$args = func_get_args();
			return html_attribute(
				call_user_func_array( $name, $args ),
				call_user_func_array( $value, $args )
			);
		};
	}
}

if( !function_exists('html_from_string') )
{
	/**
	 * @param string $string
	 * @return string
	 */
	function html_from_string( $string )
	{
		if( debug_assert( is_string($string), var_dump_human_compact( $string ) ) )
		{
			$ret = htmlspecialchars( $string, htmlspecialchars_flags() );
		}
		else
		{
			$ret = '';
		}
		return $ret;
	}
}

if( !function_exists('htmlspecialchars_flags') )
{
	/**
	 * @return int
	 */
	function htmlspecialchars_flags()
	{
		switch( Doctype::get() )
		{
			case Doctype::VER_HTML_4_01:
				$flags = ENT_HTML401;
			break;
			case Doctype::VER_HTML_5:
				$flags = ENT_HTML5;
			break;
			default:
				if( Doctype::isXhtml() )
				{
					$flags = ENT_XHTML;
				}
				else
				{
					debug_enforce( false, "Unhandled doctype ".Doctype::get() );
					$flags = null;
				}
			break;
		}
		return $flags;
	}
}