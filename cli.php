<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)

if( !function_exists('cli_parse') )
{
	/**
	 * @param array $argv
	 * @param array $inputs
	 *
	 * @return array ['param'=>$param,'flag'=>$flag,'input'=>$input]
	 */
	function cli_parse($argv, $inputs = array())
	{
		$ret = array(
			'param' => array(),
			'flag'  => array(),
			'input' => array()
		);
		$n   = false;

		foreach($argv as $arg)
		{
			// named param
			if(substr($arg, 0, 2) === '--')
			{
				$value = preg_split('/[= ]/', $arg, 2);
				$param = substr(array_shift($value), 2);
				$value = join('', $value);

				$ret['param'][$param] = '' != ($value) ? $value : true;
				continue;
			}
			// flag
			if(substr($arg, 0, 1) === '-')
			{
				for($i = 1; isset($arg[$i]); $i++)
				{
					$flag = substr($arg, $i, 1);
					if($flag !== '-')
					{
						$ret['flag'][$flag] = (substr($arg, $i + 1, 1) == '-') ? false : true;
					}
				}
				continue;
			}
			if(substr($arg, 0, 1) === '+')
			{
				$flag = substr($arg, 1, 1);
				$ret['flag'][$flag] = true;
				continue;
			}

			if(count($inputs) && $n)
			{
				$ret['input'][array_shift($inputs)] = $arg;
			}
			else
			{
				$ret['input'][] = $arg;
			}
			$n = true;
		}

		return $ret;
	}
}


if( !function_exists('cli_serialize') )
{
	/**
	 * @param array $cli
	 *
	 * @return string
	 */
	function cli_serialize($cli)
	{
		debug_assert(array_key_exists('param', $cli) && array_key_exists('flag', $cli) && array_key_exists('input', $cli));
		$ret = $cli['input'][0];
		foreach($cli['param'] as $name => $val)
		{
			$ret .= ' --'.$name.'='.escapeshellarg($val);
		}
		foreach($cli['flag'] as $name => $val)
		{
			$ret .= ' -'.$name.' '.escapeshellarg($val).' ';
		}
		$cnt = count($cli['input']);
		for($i=1; $i < $cnt; $i++)
		{
			$ret .= ' '.$cli['input'][$i];
		}
		return $ret;
	}
}

if( !function_exists('isCLI') )
{
	/**
	 * @return bool
	 */
	function isCLI()
	{
		return (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']));
	}
}