<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


if( !function_exists('locale') )
{
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

		if( debug_assert(isset($locale), "locale {$lang} isn't defined") )
		{
			$config->lang = $lang;
			$config->locale = object($locale);
		}
	}
}

if( !function_exists('tr') )
{
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
}