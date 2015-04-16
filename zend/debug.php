<?php

if( class_exists('Zend_Log') )
{
	debug_assert(function() {
		foreach(array(
			LOG_EMERG   => Zend_Log::EMERG,
			LOG_ALERT   => Zend_Log::ALERT,
			LOG_CRIT    => Zend_Log::CRIT,
			LOG_ERR     => Zend_Log::ERR,
			LOG_WARNING => Zend_Log::WARN,
			LOG_NOTICE  => Zend_Log::NOTICE,
			LOG_INFO    => Zend_Log::INFO,
			LOG_DEBUG   => Zend_Log::DEBUG
		) as $k => $v)
		{
			if( $k != $v )
			{
				return false;
			}
		}
		return true;
	}, 'One of log levels differ. Update the library.');
}