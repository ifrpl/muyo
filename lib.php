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
		'10.10.5.117',
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

/**
 * @param string $dir
 *
 * @return array
 */
function ifr_dir_flatten($dir)
{
	$ret = array();
	foreach ( scandir($dir) as $fs_entity )
	{
		if ( $fs_entity == '.' || $fs_entity == '..' )
			continue;

		$fs_entity = $dir . DIRECTORY_SEPARATOR . $fs_entity;

		if ( is_dir($fs_entity) )
		{
			$ret = array_merge($ret, ifr_dir_flatten($fs_entity));
		}
		else
		{
			$ret []= $fs_entity;
		}
	}
	return $ret;
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
 * @param      $from
 * @param      $to
 * @param bool $to_as_root
 *
 * @return string
 */
function ifr_path_rel($from, $to, $to_as_root = false)
{
	$from = realpath($from);
	$to = realpath($to);

	assert( is_string($from) && is_string($to) );

	$from_cnt = strlen($from);
	$to_cnt = strlen($to);
	$min_cnt = min($from_cnt,$to_cnt);

	if ( $to_as_root )
		assert( $from_cnt > $to_cnt );

	// traverse through equal path
	for( $i = 0; $i < $min_cnt; $i++)
	{
		if ( $from[$i] !== $to[$i] )
			break;
	}

	// make sure we're on last separator
	while( $i > 0 )
	{
		if ( $from[$i] === DIRECTORY_SEPARATOR )
		{
			if ( !ifr_escaped(substr($from, 0, $i+1)) ) // i hope that optimizer will work there
			{
				break;
			}
		}
		$i--;
	}

	$from = substr($from, $i);
	$from_cnt = $from_cnt - $i;
	$to = substr($to, $i);
	$to_cnt = $to_cnt - $i;

	for ( $i = 1; $i < $to_cnt; $i++ )
	{
		for ( $j = $i+2; $j < $to_cnt; $j++ )
		{
			if ( $to[$j] === DIRECTORY_SEPARATOR )
			{
				$to = substr($to, 0, $i-1).'..'.substr($to, $j, $to_cnt);
				$to_cnt -= $j-$i+1;
				$to_cnt +=2;
				$i += 2;
				break;
			}
			else if ( $j === $to_cnt-1 )
			{
				$to = substr($to, 0, $i-1);
			}
		}
	}

	if ( !$to[$to_cnt-1] != DIRECTORY_SEPARATOR )
	{
		$to .= DIRECTORY_SEPARATOR;
		$to_cnt++;
	}

	if ( !$to_as_root )
	{
		$from = substr($from, 1);
	}

	$ret = $to.$from;

	return $ret;
}

assert_options(ASSERT_QUIET_EVAL, true);
assert_options(ASSERT_WARNING, false);

/**
 * @param      $assertion
 * @param null $message
 * @param int  $level
 *
 * @throws Exception
 */
function ifr_assert($assertion, $message = null, $level = Zend_Log::WARN)
{
	if(!assert($assertion))
	{
		if(!$message)
		{
			$message = "Assertion of '{$assertion}' is failed";
		}

		$exc = new Exception($message);
		if(class_exists('Zend_Controller_Action_HelperBroker'))
		{
			Zend_Controller_Action_HelperBroker::getStaticHelper('log')->log($exc, $level);
		}

		throw $exc;
	}
}

/**
 * @param array $target
 * @param string|int|float $target_key
 * @param array $to_insert
 */
function array_insert_before(&$target,$target_key,$to_insert)
{
	$tmp = array();
	foreach($target as $k=>$v)
	{
		$tmp[$k] = $v;
		unset($target[$k]);
	}
	foreach($tmp as $k=>$v)
	{
		if( $k === $target_key )
		{
			foreach( $to_insert as $ik=>$iv )
			{
				$target[$ik] = $iv;
			}
		}
		$target[$k] = $v;
	}
}