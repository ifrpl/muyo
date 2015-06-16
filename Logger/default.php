<?php

if( !function_exists('logger_default') )
{
    function logger_default($log_level = null,
                            $eol = PHP_EOL)
    {
        if(null == $log_level)
        {
            $log_level = get_default_log_level();
        }

        return function( $message, $level = LOG_INFO ) use($log_level, $eol)
        {
            if( $level > $log_level )
            {
                return;
            }

            if( isCLI() )
            {
                $now = now();
                $levelStr = log_level_str($level);

                if( !is_array($message) )
                {
                    $message = explode($eol, $message);
                }

                $message = array_map(
                    function($msg) use($now, $levelStr)
                    {
                        return sprintf("[%7s] %s", $levelStr, $msg);
                    },
                    $message
                );

                $msg = implode(PHP_EOL, $message) ;

                log_output($msg, $level);
            }
            else
            {
                if(PHP_EOL != $eol)
                {
                    $message = implode(PHP_EOL, explode($eol, $message)) ;
                }

                log_syslog( $message, $level );
            }
        };
    }


}