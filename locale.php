<?php

/**
 * @param string $lang
 */
function locale($lang)
{
	global $config;

	$lang = str_replace('..','',$lang);
	$lang = str_replace('/','',$lang);

	if(file_exists($file = '../locale/'.$lang.'.php'))
	{
		require_once $file;
	}
	elseif(file_exists($file = '../locale/en_US.php'))
	{
		require_once $file;
	}
	else
	{
		$locale = array();
	}

	$config->lang = $lang;
	$config->locale = object($locale);
}

/**
 * @param string $ret
 *
 * @return mixed
 */
function tr($ret)
{
	global $config;

	if(!isset($config->locale->{$ret}))
	{
		file_put_contents(
			ROOT_PATH.'/locale/'.$config->lang.'.found',
			"\$locale['{$ret}'] = '{$ret}';\n",
			FILE_APPEND
		);
	}

	return isset($config->locale->$ret)?$config->locale->$ret:$ret;
}