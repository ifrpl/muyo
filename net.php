<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


/**
 * @return string
 */
function ifr_protocol()
{
	if( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) )
	{
		if( $_SERVER['HTTP_X_FORWARDED_PROTO']==='https' )
		{
			return 'https';
		}

		return 'http';
	}
	/*apache + variants specific way of checking for https*/
	if( isset($_SERVER['HTTPS'])
			&& ($_SERVER['HTTPS']==='on' || $_SERVER['HTTPS']==1)
	)
	{
		return 'https';
	}
	/*nginx way of checking for https*/
	if( isset($_SERVER['SERVER_PORT'])
			&& ($_SERVER['SERVER_PORT']==='443')
	)
	{
		return 'https';
	}

	return 'http';
}

/**
 * @param bool $paramName
 * @param bool $default
 *
 * @return array|bool
 */
function request($paramName=false,$default=false)
{
	$params = requestGet();

	foreach($_POST as $key=>$value)
	{
		$params[$key]=$value;
	}

	return $paramName?(isset($params[$paramName])?$params[$paramName]:$default):$params;
}

/**
 * @param bool $paramName
 * @param bool $default
 *
 * @return array|bool
 */
function requestGet($paramName=false,$default=false)
{
	global $config;
	//        $request=$_SERVER["REQUEST_URI"];

	$request = isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:(isset($_SERVER['REDIRECT_URL'])?$_SERVER['REDIRECT_URL']:$_SERVER["REQUEST_URI"]);
	$request .= isset($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING']:'';

	if(isset($config->nginx))
	{
		if($config->nginx == 1)
		{
			$request = urldecode($request);
		}
	}

	if(isset($config->request))
	{
		$names = (array)$config->request;
	}
	else
	{
		$names = array();
	}

	$params=array();

	if(strpos($request,'?',0)!==false)
	{
		list($path,$paramsStr)=explode('?',$request);
	}
	else
	{
		$path = $request;
		$paramsStr='';
	}
	$path = explode('/',trim($path,'/'));

	while($node = array_shift($path))
	{
		if($node!='')
		{
			if($name = array_shift($names))
			{
				$params[$name]=$node;
			}
			else
			{
				$value = array_shift($path);
				$params[$node] = $value;
			}
		}
	}

	if($paramsStr!='')
	{
		$paramsArray=explode('&',$paramsStr);

		foreach($paramsArray as $param)
		{
			if(strpos($request,'=',0)!==false)
			{
				list($key,$value)=explode('=',$param);
			}
			else
			{
				$key = $param;
				$value = true;
			}

			$params[$key]=$value;
		}
	}

	return $paramName?(isset($params[$paramName])?$params[$paramName]:$default):$params;
}

/**
 * @param array $params
 * @param array $mod
 *
 * @return string
 */
function url($params=array(),$mod=array())
{
	global $config;

	if(isset($GLOBALS['params']))
	{
		$names = $GLOBALS['params'];
	}
	else
	{
		$names = array();
	}

	$chunks = array();

	foreach($mod as $key=>$value)
	{
		$params[$key]=$value;
	}

	foreach($params as $paramName=>$paramValue)
	{
		if($paramValue!== null)
		{
			if(array_search($paramName,$names,true)===false)
			{
				$chunks[] = $paramName;
			}
			$chunks[] = $paramValue;
		}
	}

	return '/'.join('/',$chunks).'/';
}

/**
 * @deprecated potentially unsafe
 * @return string
 */
function getClientIP()
{
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	else
	{
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return $ip;
}

if( !function_exists('http_get') )
{
	function http_get($url,$args = array(),$info = null)
	{
		debug_assert(null === $info,'http_get $info is not implemented');
		if( !empty($args) )
		{
			$url .= '?'.implode('&',array_map_val($args,function($val,$name)
			{
				return $name.'='.urlencode($val);
			}));
		}
		return file_get_contents($url);
	}
}

function http_build_query_for_curl( $arrays, &$new = array(), $prefix = null ) {

    if ( is_object( $arrays ) ) {
        $arrays = get_object_vars( $arrays );
    }

    foreach ( $arrays AS $key => $value ) {
        $k = isset( $prefix ) ? $prefix . '[' . $key . ']' : $key;
        if ( is_array( $value ) OR is_object( $value )  ) {
            http_build_query_for_curl( $value, $new, $k );
        } else {
            $new[$k] = $value;
        }
    }
}

/**
 * Function for encoding non-ASCII character sets in various portions of a RFC 822 message header
 *
 * @link http://tools.ietf.org/html/rfc2047
 * @param string $input
 * @param string $charset
 *
 * @return string
 */
function mime_header_encode($input, $charset = 'ISO-8859-1')
{
	debug_enforce( is_string($input), "Invalid parameters" );

	preg_match_all('/(\s?\w*[\x80-\xFF]+\w*\s?)/', $input, $matches);
	foreach($matches[1] as $value)
	{
		$replacement = preg_replace('/([\x20\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
		$input       = str_replace($value, '=?'.$charset.'?Q?'.$replacement.'?=', $input);
	}

	return wordwrap($input, 75, "\n\t", true);
}

///**
// * @link http://tools.ietf.org/html/rfc2388
// * @param mixed $data
// * @param string $name
// * @return string
// */
//function mime_multipart_formdata_encode($data,$name='')
//{
//}

/**
 * Maps value of Content-Type parameter to string (without ending CRLF)
 *
 * @fixme Break to multiple lines if length > 70
 *
 * @param string $type
 * @param array $args
 *
 * @return string
 */
function mime_header_contenttype_map($type,$args)
{
	$args = array_map_val($args,function($val,$name){ debug_enforce(is_string($val)); return "$name=\"$val\""; });
	$tmp = array($type);
	foreach($args as $k => $v)
	{
		$tmp[$k] = $v;
	}
	return implode('; ',$tmp);
}

/**
 * @param string $name
 * @param mixed $value
 * @return string
 */
function mime_header_entry_map($value,$name)
{
	if( debug_assert(is_string($name),"Invalid parameters ") )
	{
		switch($name)
		{
			case 'Content-Type':
				arrayize($value);
				$value = mime_header_contenttype_map($value[0],array_filter_key($value,tuple_get(1,'is_string')));
			break;
			case 'Content-Disposition':
				$value['name'] = mime_header_encode($value['name']);
				$value = mime_header_contenttype_map($value[0],array_filter_key($value,tuple_get(1,'is_string')));
			break;
			default:
				$dump = var_dump_human_compact($value);
				debug_enforce(is_string($value),"Could not map '$name'=>'$dump'");
			break;
		}
		return "$name: $value\r\n";
	}
	else
	{
		return '';
	}
}

/**
 * @param mixed $form
 * @param string $prefix
 * @return array
 */
function form_flatten($form,$prefix = '')
{
	$ret = array();

	if( is_object($form) )
	{
		$form = get_object_vars($form);
	}

	if( is_array($form) )
	{
		if( is_array_assoc($form) )
		{
			foreach($form as $k1 => $v1)
			{
				$ret = array_merge($ret,form_flatten($v1, empty($prefix) ? $k1 : $prefix."[$k1]"));
			}
		}
		elseif( is_array_list($form) )
		{
			foreach($form as $k1 => $v1)
			{
				$ret = array_merge($ret,form_flatten($v1, empty($prefix) ? $k1 : $prefix."[]"));
			}
		}
		else
		{
			debug_assert( false, "Mixed array cannot be encoded by multipart_form_data_encode." );
		}
	}
	else
	{
		$ret []= array(
			'name' => $prefix,
			'value'=> $form,
		);
	}
	return $ret;
}

/**
 * @link http://tools.ietf.org/html/rfc2046
 * @param array $parts ['Content-Disposition'=>['form-data','name'=>'counts'],'value'=>'partcontent']
 * @param array $headers ['Content-Type'=>['multipart/form-data','boundary'=>'VeryUniqueBoundary']]
 * @return array
 */
function mime_multipart_encode($parts,$headers = array())
{
	debug_enforce(is_array($parts) && is_array($headers));

	$parts_contain = function($boundary)use($parts)
	{
		return array_contains($parts,function($part)use($boundary){ str_contains($part['value'],$boundary); });
	};

	if( !array_key_exists('Content-Type',$headers) )
	{
		$headers['Content-Type'] = array();
	}

	if( !isset($headers['Content-Type'][0]) )
	{
		$headers['Content-Type'] = array_merge(array('multipart/form-data'),$headers['Content-Type']);
	}

	if( !isset($headers['Content-Type']['boundary']) || $headers['Content-Type']['boundary'] === '""' )
	{
		$headers['Content-Type']['boundary'] = '';
		$empty_header = mime_header_entry_map($headers['Content-Type'],'Content-Type');
		$boundaryCnt = 70 - strlen($empty_header) - 2; // FIXME: including CRLF ?
		do
		{
			$boundary = str_ascii7_prand($boundaryCnt, function($char)
			{
				return ctype_alnum($char)
					|| $char === "'"
					|| $char === "("
					|| $char === ")"
					|| $char === "+"
					|| $char === "_"
					|| $char === ","
					|| $char === "-"
					|| $char === "."
					|| $char === "/"
					|| $char === ":"
					|| $char === "="
					|| $char === "?"
				;
			});
		} while( $parts_contain($boundary) );
		$headers['Content-Type']['boundary'] = $boundary;
	}
	else
	{
		$boundary = $headers['Content-Type']['boundary'];
	}

	if( array_all($parts,function($val){ return !(is_array($val) && array_key_exists('value',$val)); }) )
	{
		$parts = array_map_val($parts, function($part){ return array('value'=>$part); });
	}

	$parts = array_map_val($parts,function($part)use($boundary)
	{
		debug_enforce(array_key_exists('value',$part));
		$content = $part['value'];
		unset($part['value']);

		$headers = array_map_val($part,'mime_header_entry_map');

		return '--'.$boundary."\r\n"
			. implode('',$headers)
			. "\r\n"
			. $content
			. "\r\n";
	});

	return array(
		implode('',$parts) . '--'.$boundary.'--',
		$headers
	);
}

/**
 * @param array $packet
 * @return string
 */
function http_assemble($packet)
{
	debug_enforce(array_key_exists('Host',$packet) && is_string($packet['Host']),'Invalid Host Header');
	$method = array_key_exists('Method',$packet) ? array_get_unset($packet,'Method') : 'GET';
	$version = array_key_exists('HTTPv',$packet) ? array_get_unset($packet,'HTTPv') : 'HTTP/1.1';
	$resource = array_key_exists('Path',$packet) ? array_get_unset($packet,'Path') : '/';
	$content = array_get_unset($packet,'Content');

	list($content,$packet) = mime_multipart_encode($content,$packet);

	$packet = array_merge(array(
		'Connection' => 'close',
		'Content-Length' => (string) strlen($content),
	),$packet);

	$ret = "$method $resource $version\r\n";
	$ret .= implode('',array_map_val($packet,'mime_header_entry_map'))
		. "\r\n"
		. $content;
	return $ret;
}

/**
 * @param array $packet
 * @return string
 */
function rest_send($packet)
{
	debug_enforce(is_array($packet));
	if( array_key_exists('Url',$packet) )
	{
		$url = @parse_url(array_get_unset($packet,'Url'));
		debug_enforce(false !== $url,'Invalid URL');

		if( array_key_exists('scheme',$url) )
		{
			$packet['Scheme'] = $url['scheme'];
		}
		$packet['Host'] = array_key_exists('host',$url) ? $url['host'] : 'localhost';
		if( array_key_exists('port',$url) )
		{
			$packet['Port'] = $url['port'];
		}
		$packet['Path'] = array_key_exists('path',$url) ? $url['path'] : '/';
	}

	if( !array_key_exists('Host',$packet) )
	{
		$packet['Host'] = 'localhost';
	}

	$port = array_key_exists('Port',$packet) ? array_get_unset($packet,'Port') : '80';
	if( !str_contains(':',$packet['Host']) )
	{
		$host = $packet['Host'];
		$packet['Host'] = "$host:$port";
	}
	else
	{
		$host = array_shift(explode(':',$packet['Host']));
	}

	$ret = '';
	switch( array_get_unset($packet,'Scheme') )
	{
		case 'http':
			$data = http_assemble($packet);

			$address = gethostbyname($host);
			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			debug_enforce(false !== $socket);

			$result = socket_connect($socket,$address, intval($port));
			debug_enforce(false !== $result);

			debug_enforce( false !== socket_write($socket, $data, strlen($data)) );

			while( $out = socket_read($socket,2048) )
			{
				$ret .= $out;
			}

			socket_close($socket);

			$needle = "\r\n\r\n";
			$ret = substr($ret,strpos($ret,$needle)+strlen($needle));
		break;
		default:
			debug_enforce(false,'Unknown scheme');
		break;
	}
	return $ret;
}

/**
 * @param string $string
 * @return array
 */
function http_accept_language_decode( $string )
{
	return array_chain(
		explode( ',', $string ),
		array_map_val_dg( str_explode_dg(';') ),
		array_map_val_dg( function( $pair )
		{
			if( 1===count( $pair ) )
			{
				$pair[]=1.0;
			}
			else
			{
				debug_enforce( str_startswith( $pair[ 1 ], 'q=' ) );
				$pair[ 1 ]=floatval( str_find_after( $pair[ 1 ], 'q=' ) );
			}
			return $pair;
		})
	);
}

/**
 * @param array $pairs
 * @return array
 */
function http_accept_language_sort( $pairs )
{
	usort( $pairs, function($a,$b)
	{
		$firstA=-1;
		$firstB=1;
		$undefined=0;
		if( 1==count($a) )
		{
			return $firstA;
		}
		if( 1==count($b) )
		{
			return $firstB;
		}
		debug_assert( count($a)==2 && count($b)==2 );
		if( $a[1]==$b[1] )
		{
			return $undefined;
		}
		if( $a[1]<$b[1] )
		{
			return $firstB;
		}
		if( $a[1]>$b[1] )
		{
			return $firstA;
		}
		debug_enforce( false );
		return $undefined;
	});
	return $pairs;
}