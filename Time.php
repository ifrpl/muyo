<?php
//          Copyright IF Research Sp. z o.o. 2013.
// Distributed under the Boost Software License, Version 1.0.
//    (See accompanying file LICENSE_1_0.txt or copy at
//          http://www.boost.org/LICENSE_1_0.txt)


class IFR_Main_Time
{
	static $_timers = array();
	static $_start = false;
	static $_stopped = false;
	static $_subtimers = array();
	static $_level = 0;
	static $_flags;

	const ENABLE_VISIBLE_STATS = 1;
	const ENABLE_VISIBLE_STATS_DEBUG = 2;
	const ENABLE_COLLECT_SERVER = 4;

	static function start($id = false)
	{
		$time = self::getTime();

		if(!$id)
		{
			$id = $time;
		}

		self::$_timers[$id] = $time;

		return $time;
	}
	static function stop($id = false)
	{
		if(!$id)
		{
			throw new Exception('No timer_id');
		}

		$time = self::getTime();

		self::$_timers[$id.'::stop'] = $time;

		return $time - self::$_timers[$id];
	}
	static function getTime()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	static function getTimers()
	{
		return self::$_timers;
	}
	static function mp($description = false,$subtimer = false)
	{
		if(self::$_start === false)
		{
			return null;
		}

		if(!isset(self::$_timers['mp']))
		{
			self::$_timers['mp'] = array();
		}

		$time = self::getTime();
		$dbg = debug_backtrace();

		if(count(self::$_timers['mp']))
		{
			$prev = end(self::$_timers['mp']);
		}
		else
		{
			$prev = array('time'=>$time);
			self::$_start = $time;
		}

		if(isset($dbg[self::$_level]['file']))
		{
			$file = $dbg[self::$_level]['file'].':'.$dbg[self::$_level]['line'];
		}
		else
		{
			$file = '';
		}

		if(!$description)
		{
			$tmp = explode('/',$file);
			$description = ' __ '.array_pop($tmp);
		}

		$diff = $time-$prev['time'];

		if($subtimer)
		{
			self::$_subtimers[$description] = $time;
			$description .= "__(SUBTIMER:__START__)";
		}
		else
		{
			if(isset(self::$_subtimers[$description]))
			{
				$subtimer .= $time-self::$_subtimers[$description];
				$description .= sprintf("__(SUBTIMER:%.10f)",$subtimer);
			}
		}

		$now = array(
			'description'=> $description,
			'time'       => sprintf("%.10f", $time),
			'diff'       => sprintf("%.10f", $diff) . ' ',
			'incremental'=> sprintf("%.10f", $time - self::$_start) . ' ',
			'file'       => $file
		);

		self::$_timers['mp'][] = $now;

		return $time;
	}

	static function mpEnable($flags = 0)
	{
		self::$_start = 0;
		self::$_flags = $flags;

		self::$_level = 1;
		self::mp('__SCRIPT_START__');
		self::$_level = 0;

		register_shutdown_function(array("IFR_Main_Time", "shutdown"));
	}

	static function mpStop()
	{
		self::$_stopped = true;
		self::$_level = 1;
		self::mp('__SCRIPT_END__');
		self::$_level = 0;
	}

	/**
	 * @static
	 * @throws Exception
	 */
	public static function shutdown()
	{
		if(!self::$_stopped)
		{
			self::$_level = 1;
			self::mp('__SCRIPT_END__');
			self::$_level = 0;
		}

		if(self::$_flags & self::ENABLE_COLLECT_SERVER)
		{
			$data = 'data='.base64_encode(serialize(array(
				'mp'=>self::$_timers['mp']
				,'SERVER_ADDR'=>$_SERVER['SERVER_ADDR']
				,'REMOTE_ADDR'=>$_SERVER['REMOTE_ADDR']
				,'HTTP_HOST'=>$_SERVER['HTTP_HOST']
				,'REQUEST_URI'=>$_SERVER['REQUEST_URI']
				,'QUERY_STRING'=>$_SERVER['QUERY_STRING']
				,'SCRIPT_FILENAME'=>$_SERVER['SCRIPT_FILENAME']
				,'POST'=>$_POST
				,'GET'=>$_GET
				,'COOKIE'=>$_COOKIE
			)));

			$ch = curl_init('http://mp.ifresearch.org/dump.php');
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_exec($ch);
			curl_close($ch);
		}


		$debug = false;
		try
		{
			if(!class_exists('Zend_Registry')) throw new Exception('Zend_Registry class not found');
			$debug = Zend_Registry::get('debug');
		}
		catch(Exception $e)
		{
			$debug = (isset($_GET['debug']) && $_GET['debug'] == 'ifresearch');
		}

		if((self::$_flags & self::ENABLE_VISIBLE_STATS) || ($debug && (self::$_flags & self::ENABLE_VISIBLE_STATS_DEBUG)))
		{
			$max_diff = 0;

			foreach(self::$_timers['mp'] as $mp)
			{
				if($mp['diff']>$max_diff)
				{
					$max_diff = $mp['diff'];
				}
			}

			echo '<hr /><br /><center>';
			echo '<table border="1" cellspacing="0" cellpadding="0" align="center">';
			echo '<tr>';
			foreach(array_keys(reset(self::$_timers['mp'])) as $value)
			{
				$skip = false;
				switch($value)
				{
					case 'time':
							$skip = true;
						break;
				}
				if(!$skip)
				{
					echo '<th>'.$value.'</th>';
				}
			}
			echo '</tr>';
			foreach(self::$_timers['mp'] as $mp)
			{
				echo '<tr>';
				foreach($mp as $key=>$value)
				{
					$skip = false;
					switch($key)
					{
						case 'diff':
								$color = floor(255 - (255*$value/$max_diff));
								echo '<td style="background-color: rgb(255,'.$color.','.$color.')">';
							break;
						case 'time':
								$skip = true;
							break;
						default:
							echo '<td>';
					}
					if(!$skip)
					{
						echo $value;
						echo '</td>';
					}
				}
				echo '</tr>';
			}
			echo '</table></center><br />';
		}
	}
}
