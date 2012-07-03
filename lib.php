<?php

function object($array=array())
{
	$obj = ((object) NULL);
	foreach($array as $key=>$value)
	{
		if(is_array($value))
		{
			$value = object($value);
		}
		$obj->$key = $value;
	}
	return $obj;
}

function debugHostAllow()
{
	$allowedHosts = array(
		'127.0.0.1',
		'127.0.1.1',
		'10.0.2.2',
		'10.10.5.103',
		'10.10.5.116',
		'10.10.101.1',
		'10.10.5.49',     //lukasz.lan.ifresearch.org
		'10.10.5.40',     //WinXP with IE7
		'10.10.5.50',     //Pejotr
		'192.168.56.1',   //VBox host
		'10.5.0.115',     //lukasz home local
		'10.5.10.135',    //lukasz home local2
		'89.191.162.220', //Lukasz home
		'87.206.45.163',
		'84.10.100.73',
		'89.69.132.33'
	);
	return ((isCLI() && APPLICATION_ENV !== 'production'))
			|| (!isCLI() && isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], $allowedHosts));
}

function ifrShowDebugOutput()
{
	if(!debugHostAllow() || (APPLICATION_ENV === 'production' && (!isset($_COOKIE['ifrShowDebug']) || $_COOKIE['ifrShowDebug'] !== 'iLuv2ki11BugsBunny!'))) return false;
	return true;
}

function printr($tab, $cli=false)
{
	if(!ifrShowDebugOutput()) return;
	$dbg = debug_backtrace();
	if(!$cli)
	{
		echo "<pre style='background-color: #efefef; border: 1px solid #aaaaaa; color:#000;'>";
		$f = "{$dbg[0]['file']}:{$dbg[0]['line']}";
		echo "<div style='font-weight: bold; background-color: #FFF15F; border-bottom: 1px solid #aaaaaa;'><a href='http://localhost:8091?message=$f'>$f</a></div>";
	}
	$tmp = print_r($tab,true);

	if($cli === 2)
	{
		$tmp = str_replace("\n",'',$tmp);
	}
	echo $tmp;
	if(!$cli)
	{
		echo "</pre>\n";
	}
	else
	{
		echo "\n";
	}
}

function printn($str='',$count=1,$print=true)
{
	if(!ifrShowDebugOutput()) return;
	if(is_bool($count))
	{
		if(!is_bool($print))
		{
			$tmp = $print;
		}

		$print = $count;

		$count = isset($tmp)?$tmp:1;
	}
	$str.=str_pad('', $count, "\n");

	$print and print($str);

	return $str;
}

function printfb($tab)
{
	if(!ifrShowDebugOutput()) return;
	header('debug: '.$tab);
}

function debug($tab, $wrapped = false)
{
	if(!ifrShowDebugOutput()) return;
	require_once('Zend/Debug.php');
	$dbg = debug_backtrace();
	$fid = $wrapped?1:0;
	echo "<div style='background-color: #efefef; border: 1px solid #aaaaaa; color:#000;'>";
	$f = "{$dbg[0]['file']}:{$dbg[0]['line']}";
	echo "<div style='font-weight: bold; background-color: #FFF15F; border-bottom: 1px solid #aaaaaa;'><a href='http://localhost:8091?message=$f'>$f</a></div>";
	Zend_Debug::dump($tab);
	echo "</div>";
	exit();
}

if(!function_exists('get_call_stack'))
{
	function get_call_stack()
	{
		if(!ifrShowDebugOutput()) return;
		$dbg = debug_backtrace();

		array_shift($dbg);
		$calls = array();
		foreach($dbg as $b)
		{
			array_push($calls, $b['file'].':'.$b['line'].' | '.$b['class'].'::'.$b['function']);
		}
		return $calls;
	}
}


function getShellTop()
{
	if(!ifrShowDebugOutput()) return;
	return shell_exec("top -bcs -n 2 -p ".getmypid());
}

function str_truncate($string, $length = 80, $etc = '...',$break_words = false, $middle = false, $nobr = true)
{
    if ($length == 0)
        return '';

    $string = str_replace('&oacute;',"�",$string);
   	if($nobr)
    {
     	$string = preg_replace('/<br[\s]*?\/*?>/', ' ', $string);
    }

    if (strlen($string) > $length) {
        $length -= min($length, strlen($etc));
        if (!$break_words && !$middle) {
            $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length+1));
        }
        if(!$middle) {
        	$string = substr($string, 0, $length);
        	return substr($string, 0, strrpos($string,' ')) . $etc;
        } else {
            return substr($string, 0, $length/2) . $etc . substr($string, -$length/2);
        }
    } else {
        return $string;
    }
}

function is_iterable($obj,$interface=false)
{
	return
		is_object($obj) ?
			$interface ?
				array_search('Iterator',class_implements($obj))!==false
				:
				true
			:
			is_array($obj)
	;
}


function isCLI()
{
      return (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']));
}

function getConfig($path, $env = null)
{
    require_once 'Lib/Config.php';
    $config = new Lib_Config($path);
    return $config->getConfig($env);
}

function getClientIP()
{
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return $ip;
}
