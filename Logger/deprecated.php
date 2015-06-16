<?php

if( !function_exists('logger_deprecated') )
{
    /**
     * @deprecated
     *
     * @param $eol
     * @return callable
     */
    function logger_deprecated($eol="\n")
    {
        return function( $message, $level=LOG_INFO )use($eol)
        {
            $msg = '';
            $now = now();
            $level = log_level_str($level);

            if( !is_array($message) )
            {
                $message = explode($eol, $message);
            }

            for($i = 0; $i<count($message); $i++)
            {
                $msg .= sprintf("[%s] [%7s] %s", $now, $level, $message[$i]);
                if($i<count($message)-1)
                {
                    $msg .= PHP_EOL;
                }
            }

            printrlog($msg);
        };
    }
}