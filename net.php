<?php

namespace net;

/**
 * @return string
 */
function ifr_protocol()
{
	return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https' : 'http';
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